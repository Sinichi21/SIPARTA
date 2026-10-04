<?php

namespace App\Livewire\AdministrationProfiles;

use App\Models\LetterheadProfile;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public LetterheadProfile $letterheadProfile;

    public string $name = '';
    public string $organization_name = '';
    public string $parent_organization = '';
    public string $sub_parent_organization = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';
    public string $city = '';

    public string $signatory_name = '';
    public string $signatory_nip = '';
    public string $signatory_position = '';

    public bool $is_default = false;
    public bool $is_active = true;

    public $logo;

    public $logo_secondary;

    public function mount(
        LetterheadProfile $letterheadProfile
    ): void {
        Gate::authorize('settings.manage');

        $this->letterheadProfile =
            $letterheadProfile;

        foreach ([
            'name',
            'organization_name',
            'parent_organization',
            'sub_parent_organization',
            'address',
            'phone',
            'email',
            'website',
            'city',
            'signatory_name',
            'signatory_nip',
            'signatory_position',
        ] as $field) {
            $this->{$field} =
                $letterheadProfile->{$field} ?? '';
        }

        $this->is_default =
            (bool) $letterheadProfile->is_default;

        $this->is_active =
            (bool) $letterheadProfile->is_active;
    }

    public function save(AuditService $audit): void
    {
        Gate::authorize('settings.manage');

        $data = $this->validate($this->rules());

        $oldValues =
            $this->letterheadProfile->getOriginal();

        $oldLogoPath =
            $this->letterheadProfile->logo_path;

        $oldSecondaryLogoPath =
            $this->letterheadProfile->logo_secondary_path;

        $newLogoPath = null;
        $newLogoOriginalName = null;
        $newSecondaryLogoPath = null;
        $newSecondaryLogoOriginalName = null;

        if ($this->logo) {
            $newLogoOriginalName =
                $this->logo->getClientOriginalName();

            $extension = strtolower(
                $this->logo->getClientOriginalExtension()
            );

            $newLogoPath = $this->logo->storeAs(
                'letterheads/logos',
                Str::uuid().'.'.$extension,
                'public'
            );
        }

        if ($this->logo_secondary) {
            $newSecondaryLogoOriginalName =
                $this->logo_secondary->getClientOriginalName();

            $extension = strtolower(
                $this->logo_secondary->getClientOriginalExtension()
            );

            $newSecondaryLogoPath = $this->logo_secondary->storeAs(
                'letterheads/logos',
                Str::uuid().'.'.$extension,
                'public'
            );
        }

        try {
            DB::transaction(
                function () use (
                    $data,
                    $newLogoPath,
                    $newLogoOriginalName,
                    $newSecondaryLogoPath,
                    $newSecondaryLogoOriginalName,
                    $audit,
                    $oldValues
                ) {
                    if ($data['is_default']) {
                        LetterheadProfile::query()
                            ->whereKeyNot(
                                $this->letterheadProfile->id
                            )
                            ->update([
                                'is_default' => false,
                            ]);
                    }

                    $payload =
                        $this->normalized($data);

                    if ($newLogoPath) {
                        $payload['logo_path'] =
                            $newLogoPath;

                        $payload['logo_original_name'] =
                            $newLogoOriginalName;
                    }

                    if ($newSecondaryLogoPath) {
                        $payload['logo_secondary_path'] =
                            $newSecondaryLogoPath;

                        $payload['logo_secondary_original_name'] =
                            $newSecondaryLogoOriginalName;
                    }

                    $payload['updated_by'] =
                        auth()->id();

                    $this->letterheadProfile
                        ->update($payload);

                    $audit->updated(
                        $this->letterheadProfile,
                        $oldValues
                    );
                }
            );
        } catch (\Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')
                    ->delete($newLogoPath);
            }

            if ($newSecondaryLogoPath) {
                Storage::disk('public')
                    ->delete($newSecondaryLogoPath);
            }

            throw $exception;
        }

        if (
            $newLogoPath
            && $oldLogoPath
            && $oldLogoPath !== $newLogoPath
        ) {
            Storage::disk('public')
                ->delete($oldLogoPath);
        }

        if (
            $newSecondaryLogoPath
            && $oldSecondaryLogoPath
            && $oldSecondaryLogoPath !== $newSecondaryLogoPath
        ) {
            Storage::disk('public')
                ->delete($oldSecondaryLogoPath);
        }

        $this->logo = null;
        $this->logo_secondary = null;
        $this->letterheadProfile->refresh();

        session()->flash(
            'success',
            'Profil kop surat berhasil diperbarui.'
        );
    }

    public function removeLogo(
        AuditService $audit
    ): void {
        Gate::authorize('settings.manage');

        $path = $this->letterheadProfile
            ->logo_path;

        if (! $path) {
            return;
        }

        $oldValues =
            $this->letterheadProfile->getOriginal();

        $this->letterheadProfile->update([
            'logo_path' => null,
            'logo_original_name' => null,
            'updated_by' => auth()->id(),
        ]);

        $audit->updated(
            $this->letterheadProfile,
            $oldValues
        );

        Storage::disk('public')->delete($path);

        session()->flash(
            'success',
            'Logo kop berhasil dihapus.'
        );
    }

    private function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'organization_name' => [
                'required',
                'string',
                'max:255',
            ],
            'parent_organization' => [
                'nullable',
                'string',
                'max:255',
            ],
            'sub_parent_organization' => ['nullable', 'string', 'max:255'],
            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'website' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:120',
            ],
            'signatory_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'signatory_nip' => [
                'nullable',
                'string',
                'max:100',
            ],
            'signatory_position' => [
                'nullable',
                'string',
                'max:255',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'logo_secondary' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    private function normalized(array $data): array
    {
        foreach ([
            'name',
            'organization_name',
            'parent_organization',
            'sub_parent_organization',
            'address',
            'phone',
            'email',
            'website',
            'city',
            'signatory_name',
            'signatory_nip',
            'signatory_position',
        ] as $key) {
            $data[$key] = filled($data[$key] ?? null)
                ? trim($data[$key])
                : null;
        }

        if ($data['is_default']) {
            $data['is_active'] = true;
        }

        unset($data['logo'], $data['logo_secondary']);

        return $data;
    }

    public function render()
    {
        return view(
            'livewire.administration-profiles.edit'
        );
    }
}
