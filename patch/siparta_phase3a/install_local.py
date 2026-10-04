from pathlib import Path
import argparse
import datetime
import shutil

parser = argparse.ArgumentParser(description='SIPARTA Phase 3A: guarded local installer')
parser.add_argument('root', nargs='?', default='.')
args = parser.parse_args()
root = Path(args.root).resolve()
source = Path(__file__).parent / 'files'

changes = {
 'app/Livewire/Letters/Show.php': [
  ('    public function publish(LetterService $service): void', '''    public string $reviewNote = '';

    private function reloadReviewLetter(\\App\\Models\\Letter $letter): void
    {
        $this->letter = $letter->load([
            'letterType', 'activityType', 'personnels.unit', 'creator', 'updater',
            'canceller', 'attachments', 'outgoingLetter.issuedLetter',
            'submissionEvents.actor',
        ]);
        $this->reviewNote = '';
    }

    public function verify(\\App\\Services\\SptReviewService $service): void
    {
        Gate::authorize('letters.verify');
        $this->validate(['reviewNote' => ['nullable', 'string', 'max:2000']]);
        $this->reloadReviewLetter($service->verify($this->letter, Auth::id(), $this->reviewNote));
        session()->flash('success', 'Pengajuan SPT berhasil diverifikasi.');
    }

    public function approve(\\App\\Services\\SptReviewService $service): void
    {
        Gate::authorize('letters.approve');
        $this->validate(['reviewNote' => ['nullable', 'string', 'max:2000']]);
        $this->reloadReviewLetter($service->approve($this->letter, Auth::id(), $this->reviewNote));
        session()->flash('success', 'Pengajuan SPT berhasil disetujui.');
    }

    public function returnForRevision(\\App\\Services\\SptReviewService $service): void
    {
        $ability = $this->letter->status === \\App\\Enums\\LetterStatus::Verified
            ? 'letters.approve' : 'letters.verify';
        Gate::authorize($ability);
        $this->validate(['reviewNote' => ['required', 'string', 'min:10', 'max:2000']]);
        $this->reloadReviewLetter($service->returnForRevision($this->letter, Auth::id(), $this->reviewNote));
        session()->flash('success', 'Pengajuan dikembalikan untuk diperbaiki.');
    }

    public function publish(LetterService $service): void'''),
 ],
 'resources/views/livewire/letters/show.blade.php': [
  ('            <x-letters.edit-action :letter="$letter" show-disabled />', '''            @if($letter->submission_reference && in_array($letter->status, [\\App\\Enums\\LetterStatus::Submitted, \\App\\Enums\\LetterStatus::Verified], true))
                @if(($letter->status === \\App\\Enums\\LetterStatus::Submitted && auth()->user()->can('letters.verify')) || ($letter->status === \\App\\Enums\\LetterStatus::Verified && auth()->user()->can('letters.approve')))
                    <span class="text-xs text-slate-500">Pemeriksaan tersedia di panel di bawah.</span>
                @endif
            @endif
            <x-letters.edit-action :letter="$letter" show-disabled />'''),
  ('    <div class="spt-detail-grid">', '''    @if($letter->submission_reference && in_array($letter->status, [\\App\\Enums\\LetterStatus::Submitted, \\App\\Enums\\LetterStatus::Verified], true))
        @if(($letter->status === \\App\\Enums\\LetterStatus::Submitted && auth()->user()->can('letters.verify')) || ($letter->status === \\App\\Enums\\LetterStatus::Verified && auth()->user()->can('letters.approve')))
            <section class="spt-section-card mb-6">
                <h2 class="spt-section-heading">Pemeriksaan Pengajuan SPT</h2>
                <p class="mb-3 text-sm text-slate-600">Periksa data kegiatan dan personil sebelum mengubah status. Pengembalian memerlukan alasan minimal 10 karakter.</p>
                <label class="block text-sm font-medium" for="spt-review-note">Catatan pemeriksaan / alasan revisi</label>
                <textarea id="spt-review-note" wire:model="reviewNote" rows="3" maxlength="2000" class="mt-2 w-full rounded-lg border border-slate-300 p-3"></textarea>
                @error('reviewNote') <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                @error('status') <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                <div class="mt-3 flex flex-wrap gap-2">
                    @if($letter->status === \\App\\Enums\\LetterStatus::Submitted)
                        <button type="button" wire:click="verify" wire:loading.attr="disabled" wire:confirm="Verifikasi pengajuan ini?" class="spt-action spt-action-primary">Verifikasi</button>
                    @else
                        <button type="button" wire:click="approve" wire:loading.attr="disabled" wire:confirm="Setujui pengajuan ini?" class="spt-action spt-action-primary">Setujui</button>
                    @endif
                    <button type="button" wire:click="returnForRevision" wire:loading.attr="disabled" wire:confirm="Kembalikan pengajuan untuk revisi?" class="spt-action spt-action-view">Kembalikan untuk Revisi</button>
                </div>
            </section>
        @endif
    @endif
    <div class="spt-detail-grid">'''),
 ],
}

planned = {}
for relative, replacements in changes.items():
    target = root / relative
    if not target.is_file():
        raise SystemExit(f'Missing {relative}; no changes made.')
    content = target.read_text(encoding='utf-8')
    if ('public function verify(\\App\\Services\\SptReviewService' in content or 'id="spt-review-note"' in content):
        raise SystemExit(f'Phase 3A already present in {relative}; no changes made.')
    if relative.endswith('Show.php') and 'public function submit(' not in content:
        raise SystemExit('Phase 2B is missing from Show.php; no changes made.')
    if relative.endswith('.blade.php') and 'Riwayat Pengajuan' not in content:
        raise SystemExit('Phase 2B is missing from the detail view; no changes made.')
    for anchor, replacement in replacements:
        count = content.count(anchor)
        if count != 1:
            raise SystemExit(f'{relative}: expected 1 anchor, found {count}: {anchor[:70]!r}; no changes made.')
        content = content.replace(anchor, replacement, 1)
    planned[target] = content

for file in source.rglob('*'):
    if file.is_file():
        target = root / file.relative_to(source)
        if target.exists():
            raise SystemExit(f'Already exists: {target.relative_to(root)}; no changes made.')

backup = root / 'storage/app/phase-backups' / ('phase3a-' + datetime.datetime.now().strftime('%Y%m%d-%H%M%S'))
for target in planned:
    destination = backup / target.relative_to(root)
    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(target, destination)
for target, content in planned.items():
    target.write_text(content, encoding='utf-8')
for file in source.rglob('*'):
    if file.is_file():
        target = root / file.relative_to(source)
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(file, target)
print('Installed Phase 3A. Backups:', backup)
print('Run: php artisan view:clear; php artisan test --filter=PhaseThreeAReviewTest; php artisan test --compact')
