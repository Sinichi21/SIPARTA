#!/usr/bin/env python3
"""Apply SIPARTA letterhead patch to LOCAL repository; does not use git or contact GitHub."""
from pathlib import Path
import shutil, sys, datetime
root=Path(sys.argv[1]).resolve() if len(sys.argv)>1 else Path.cwd()
source=Path(__file__).resolve().parent/'payload'
backup=root/'storage/app/phase-backups'/('dirjen-letterhead-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S'))
changes={}
def change(path, old, new, count=1):
    f=root/path
    if not f.is_file(): raise RuntimeError('File missing: '+str(path))
    value=changes.get(path, f.read_text(encoding='utf-8-sig'))
    if new in value: return
    if value.count(old)!=count: raise RuntimeError(f'Unexpected content at {path}: expected {count} marker(s), found {value.count(old)}: {old[:90]}')
    changes[path]=value.replace(old,new,count)

# Eloquent fillable: existing sub field is deliberately nullable and does not overwrite older profile data.
change('app/Models/LetterheadProfile.php', "'name','organization_name','parent_organization','address'", "'name','organization_name','parent_organization','sub_parent_organization','address'")
for p in ('app/Livewire/AdministrationProfiles/Create.php','app/Livewire/AdministrationProfiles/Edit.php'):
    change(p, "public string $parent_organization = '';", "public string $parent_organization = '';\n    public string $sub_parent_organization = '';" )
    # In Edit these fields appear twice; add to mount and normalized, in Create to normalized only.
    old="            'parent_organization',\n"
    f=root/p
    count=(changes.get(p,f.read_text(encoding='utf-8-sig'))).count(old)
    if count not in (1,2): raise RuntimeError(f'Unexpected profile field list in {p}: {count}')
    change(p,old, "            'parent_organization',\n            'sub_parent_organization',\n", count)
    change(p,"            'parent_organization' => [\n                'nullable',\n                'string',\n                'max:255',\n            ],", "            'parent_organization' => [\n                'nullable',\n                'string',\n                'max:255',\n            ],\n            'sub_parent_organization' => ['nullable', 'string', 'max:255'],")
# Insert separate Dirjen field without changing the rest of the setting form.
p='resources/views/livewire/administration-profiles/_form.blade.php'
anchor="""        <label class="grid gap-1.5 text-sm font-medium md:col-span-2">
            <span>Alamat</span>"""
block="""        <label class="grid gap-1.5 text-sm font-medium md:col-span-2">
            <span>Sub Instansi Induk / Direktorat Jenderal</span>
            <input wire:model="sub_parent_organization" class="rounded-xl border-slate-200" placeholder="Contoh: Direktorat Jenderal Infrastruktur Digital">
            @error('sub_parent_organization')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

"""
change(p,anchor,block+anchor)
# For reference in new snapshots (issued PDF bytes remain immutable).
change('app/Services/LetterDocumentService.php',"            'parent_organization' => $profile->parent_organization,", "            'parent_organization' => $profile->parent_organization,\n            'sub_parent_organization' => $profile->sub_parent_organization,")
change('app/Services/IssuedLetterArchiveService.php',"                'parent_organization' => $letter->letterheadProfile?->parent_organization,", "                'parent_organization' => $letter->letterheadProfile?->parent_organization,\n                'sub_parent_organization' => $letter->letterheadProfile?->sub_parent_organization,")
change('app/Services/OutgoingLetterTemplateRenderer.php',"'instansi_induk'=>$head?->parent_organization,", "'instansi_induk'=>$head?->parent_organization,\n            'sub_instansi_induk'=>$head?->sub_parent_organization,'dirjen'=>$head?->sub_parent_organization,")
# Update the existing older tests for new line drawing and Dirjen field; preserve rest of test suite.
p='tests/Feature/PhaseFifteenTwoDomPdfLetterheadTest.php'
f=root/p; t=f.read_text(encoding='utf-8-sig')
if 'border-bottom: 3px double #0f172a' in t:
    change(p,"'border-bottom: 3px double #0f172a'", "'border-top: 1px solid #555555'")
    # Old width/separator tests need to match updated requested design.
    change(p,"'width=\"15%\"'", "'width=\"20%\"'")
    change(p,"'width=\"85%\"'", "'width=\"80%\"'")
    change(p,"'width=\"24%\"'", "'width=\"28%\"'")
    change(p,"'width=\"76%\"'", "'width=\"72%\"'")
    change(p,"$this->assertStringContainsString('border-right: 1px solid #64748b', $html);", "$this->assertStringNotContainsString('border-right:', $html);")
elif 'border-bottom: 3px double #555555' in t:
    change(p,"'border-bottom: 3px double #555555'", "'border-top: 1px solid #555555'")
else: raise RuntimeError('Unexpected DomPdf letterhead test; do not overwrite manually changed tests')
p='tests/Feature/PhaseFifteenTwoLetterheadConsistencyTest.php'
f=root/p; t=f.read_text(encoding='utf-8-sig')
if "'border-bottom: 3px double #0f172a'" in t:
    change(p,"'border-bottom: 3px double #0f172a'", "'border-top: 1px solid #555555'")
elif "'border-bottom: 3px double #555555'" in t:
    change(p,"'border-bottom: 3px double #555555'", "'border-top: 1px solid #555555'")
else: raise RuntimeError('Unexpected consistency test; do not overwrite manually changed tests')

# Validate everything before applying anything. No partial edits on anchor mismatch.
blade='resources/views/components/official-letterhead.blade.php'
if not (root/blade).is_file(): raise RuntimeError('Missing shared blade renderer')
if not (root/'database/migrations/2026_09_29_230000_create_letterhead_profiles_table.php').is_file():
    raise RuntimeError('Unexpected project layout, missing baseline migration')
migration='database/migrations/2026_10_04_000001_add_sub_parent_organization_to_letterhead_profiles.php'
if (root/migration).exists(): raise RuntimeError('Migration name already exists; inspect it before applying')
for path, text in changes.items():
    dest=backup/path; dest.parent.mkdir(parents=True,exist_ok=True); shutil.copy2(root/path,dest)
(backup/blade).parent.mkdir(parents=True,exist_ok=True)
shutil.copy2(root/blade, backup/blade)
for path,text in changes.items(): (root/path).write_text(text,encoding='utf-8',newline='\n')
(root/blade).write_bytes((source/blade).read_bytes())
(root/migration).write_bytes((source/migration).read_bytes())
print('PATCH APPLIED (LOCAL ONLY). Backup:',backup)
print('Files changed:',len(changes)+2)
print('Next: php artisan migrate; php artisan view:clear; php artisan test --filter=Letterhead')
