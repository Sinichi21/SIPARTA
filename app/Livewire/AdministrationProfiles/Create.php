<?php

namespace App\Livewire\AdministrationProfiles;

use App\Models\LetterheadProfile;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $organization_name = '';
    public string $parent_organization = '';
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

    public function mount(): void
    {
        Gate::authorize('settings.manage');
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('settings.manage');

        $data = $this->validate($this->rules());

        $logoPath = null;
        $logoOriginalName = null;

        if ($this->logo) {
            $logoOriginalName = $this->logo
                ->getClientOriginalName();

            $extension = strtolower(
                $this->logo->getClientOriginalExtension()
            );

            $logoPath = $this->logo->storeAs(
                'letterheads/logos',
                Str::uuid().'.'.$extension,
                'public'
            );
        }

        try {
            $profile = DB::transaction(
                function () use (
                    $data,
                    $logoPath,
                    $logoOriginalName,
                    $audit
                ) {
                    if ($data['is_default']) {
                        LetterheadProfile::query()
                            ->update([
                                'is_default' => false,
                            ]);
                    }

                    $profile =
                        LetterheadProfile::create([
                            ...$this->normalized($data),
                            'logo_path' => $logoPath,
                            'logo_original_name' =>
                                $logoOriginalName,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);

                    $audit->created($profile);

                    return $profile;
                }
            );
        } catch (\Throwable $exception) {
            if ($logoPath) {
                \Storage::disk('public')
                    ->delete($logoPath);
            }

            throw $exception;
        }

        session()->flash(
            'success',
            'Profil kop surat berhasil dibuat.'
        );

        return $this->redirectRoute(
            'administration-profiles.edit',
            ['letterheadProfile' => $profile->id],
            navigate: true
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

        unset($data['logo']);

        return $data;
    }

    public function render()
    {
        return view(
            'livewire.administration-profiles.create'
        );
    }
}
