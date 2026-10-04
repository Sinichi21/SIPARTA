from pathlib import Path
import argparse, shutil, datetime

parser=argparse.ArgumentParser(description='Phase 2B local-only, guarded installer')
parser.add_argument('root',nargs='?',default='.')
args=parser.parse_args()
root=Path(args.root).resolve(); src=Path(__file__).parent/'files'

edits={
 'app/Models/Letter.php': [
  ('    public function documentSnapshot(): HasOne', '    public function submissionEvents(): HasMany\n    {\n        return $this->hasMany(SptSubmissionEvent::class)->orderByDesc(\'id\');\n    }\n\n    public function documentSnapshot(): HasOne'),
  ("        'number',", "        'number',\n        'submission_reference',\n        'submitted_at',\n        'submitted_by',"),
  ("            'letter_date' => 'date',", "            'letter_date' => 'date',\n            'submitted_at' => 'datetime',"),
 ],
 'app/Livewire/Letters/Show.php': [
  ("    public function publish(LetterService $service): void", '''    public function submit(\\App\\Services\\SptSubmissionService $service): void
    {
        Gate::authorize('letters.submit');
        $this->letter = $service->submit($this->letter, Auth::id());
        $this->letter->load(['letterType','activityType','personnels.unit','creator','updater',
            'canceller','attachments','outgoingLetter.issuedLetter','submissionEvents.actor']);
        session()->flash('success', 'Pengajuan SPT berhasil dikirim.');
    }

    public function publish(LetterService $service): void'''),
  ("            'attachments',\n            'outgoingLetter.issuedLetter',", "            'attachments',\n            'submissionEvents.actor',\n            'outgoingLetter.issuedLetter',", 3),
 ],
 'resources/views/livewire/letters/show.blade.php': [
  ('            <x-letters.edit-action :letter="$letter" show-disabled />', '''            @if($letter->status === \\App\\Enums\\LetterStatus::Draft && $letter->source !== 'import')
                @can('letters.submit')
                    <button type="button" wire:click="submit" wire:confirm="Kirim pengajuan SPT untuk diproses?" wire:loading.attr="disabled" class="spt-action spt-action-primary">Ajukan SPT</button>
                @endcan
            @endif
            <x-letters.edit-action :letter="$letter" show-disabled />'''),
  ('    <div class="spt-detail-grid">', '''    @if($letter->submission_reference)
        <section class="spt-section-card mb-6">
            <h2 class="spt-section-heading">Riwayat Pengajuan</h2>
            <p class="text-sm">Nomor Pengajuan: <strong>{{ $letter->submission_reference }}</strong></p>
            <p class="text-xs text-slate-500">Nomor ini bukan nomor surat resmi.</p>
            @foreach($letter->submissionEvents as $event)
                <p class="mt-2 text-sm">{{ $event->created_at?->translatedFormat('d M Y H:i') }} — {{ $event->actor?->name ?: 'Pengguna' }}: {{ $event->event === 'submitted' ? 'Diajukan' : $event->event }}</p>
            @endforeach
        </section>
    @endif
    <div class="spt-detail-grid">'''),
 ],
}
# Validate every anchor and destination before any write.
planned={}
for rel, replacements in edits.items():
 p=root/rel
 if not p.is_file(): raise SystemExit(f'Missing file {rel}; no changes made.')
 value=p.read_text(encoding='utf-8')
 for entry in replacements:
  anchor,new,*expected=entry
  needed=expected[0] if expected else 1
  count=value.count(anchor)
  if count!=needed: raise SystemExit(f'{rel}: expected {needed} anchor(s), found {count}: {anchor[:80]!r}; no changes made.')
  value=value.replace(anchor,new,needed)
 planned[p]=value
for p in src.rglob('*'):
 if p.is_file():
  dest=root/p.relative_to(src)
  if dest.exists(): raise SystemExit(f'File already exists: {dest.relative_to(root)}; no changes made.')
backup=root/'storage/app/phase-backups'/('phase2b-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S'))
for p in planned:
 b=backup/p.relative_to(root);b.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,b)
for p,value in planned.items():p.write_text(value,encoding='utf-8')
for p in src.rglob('*'):
 if p.is_file():
  d=root/p.relative_to(src);d.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,d)
print(f'Phase 2B installed. Backup: {backup}')
print('Next: php artisan migrate; php artisan test --filter=PhaseTwoBSubmissionTest; php artisan test --compact')
