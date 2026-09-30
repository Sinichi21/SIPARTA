<?php

namespace App\Livewire\AdministrationProfiles;

use App\Models\LetterheadProfile;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('settings.view');
    }

    public function setDefault(
        int $id,
        AuditService $audit
    ): void {
        Gate::authorize('settings.manage');

        $profile = LetterheadProfile::query()
            ->findOrFail($id);

        $old = $profile->getOriginal();

        LetterheadProfile::query()
            ->whereKeyNot($profile->id)
            ->update(['is_default' => false]);

        $profile->update([
            'is_default' => true,
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        $audit->updated($profile, $old);

        session()->flash(
            'success',
            'Profil kop default berhasil diperbarui.'
        );
    }

    public function toggle(
        int $id,
        AuditService $audit
    ): void {
        Gate::authorize('settings.manage');

        $profile = LetterheadProfile::query()
            ->findOrFail($id);

        if (
            $profile->is_default
            && $profile->is_active
        ) {
            $this->addError(
                'profile',
                'Profil default tidak dapat dinonaktifkan. Tetapkan profil default lain terlebih dahulu.'
            );

            return;
        }

        $old = $profile->getOriginal();

        $profile->update([
            'is_active' => ! $profile->is_active,
            'updated_by' => auth()->id(),
        ]);

        $audit->updated($profile, $old);
    }

    public function render()
    {
        return view(
            'livewire.administration-profiles.index',
            [
                'profiles' => LetterheadProfile::query()
                    ->when(
                        filled($this->search),
                        function ($query) {
                            $search = trim($this->search);

                            $query->where(
                                fn ($query) => $query
                                    ->where(
                                        'name',
                                        'ilike',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'organization_name',
                                        'ilike',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'signatory_name',
                                        'ilike',
                                        "%{$search}%"
                                    )
                            );
                        }
                    )
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->paginate(15),
            ]
        );
    }
}
