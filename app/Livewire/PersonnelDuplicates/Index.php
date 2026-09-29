<?php

namespace App\Livewire\PersonnelDuplicates;

use App\Models\Personnel;
use App\Services\PersonnelMergeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    /** @var array<string, int|string> */
    public array $primarySelections = [];

    public ?int $selectedPersonnelId = null;

    public string $manualPrimarySearch = '';

    public string $manualDuplicateSearch = '';

    public ?int $manualPrimaryId = null;

    /** @var array<int, int|string> */
    public array $manualDuplicateIds = [];

    public function mount(): void
    {
        Gate::authorize('personnels.merge');
    }

    public function mergeGroup(
        string $groupKey,
        PersonnelMergeService $mergeService
    ): void {
        Gate::authorize('personnels.merge');

        $groups = $this->duplicateGroups($mergeService);
        $group = $groups->get($groupKey);

        if (! $group) {
            throw ValidationException::withMessages([
                'merge' => 'Kelompok duplikat tidak ditemukan. Muat ulang halaman.',
            ]);
        }

        $primaryId = (int) ($this->primarySelections[$groupKey] ?? 0);
        $primary = $group->firstWhere('id', $primaryId);

        if (! $primary) {
            throw ValidationException::withMessages([
                'merge' => 'Pilih record utama terlebih dahulu.',
            ]);
        }

        $duplicateIds = $group
            ->where('id', '!=', $primaryId)
            ->pluck('id')
            ->all();

        $mergeService->merge($primary, $duplicateIds);

        unset($this->primarySelections[$groupKey]);

        session()->flash(
            'success',
            'Personil duplikat berhasil digabungkan. Seluruh relasi SPT tetap dipertahankan.'
        );
    }


    public function mergeManual(
        PersonnelMergeService $mergeService
    ): void {
        Gate::authorize('personnels.merge');

        if (! $this->manualPrimaryId) {
            throw ValidationException::withMessages([
                'manual' => 'Pilih personil utama terlebih dahulu.',
            ]);
        }

        $duplicateIds = collect($this->manualDuplicateIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $this->manualPrimaryId)
            ->unique()
            ->values()
            ->all();

        if ($duplicateIds === []) {
            throw ValidationException::withMessages([
                'manual' => 'Pilih minimal satu record yang akan digabungkan.',
            ]);
        }

        $primary = Personnel::findOrFail($this->manualPrimaryId);

        $mergeService->merge($primary, $duplicateIds);

        $this->manualPrimaryId = null;
        $this->manualDuplicateIds = [];
        $this->manualPrimarySearch = '';
        $this->manualDuplicateSearch = '';

        session()->flash(
            'success',
            'Merge manual berhasil. Riwayat SPT dari record yang dipilih telah dipindahkan ke personil utama.'
        );
    }

    public function removeSuspicious(
        int $personnelId,
        PersonnelMergeService $mergeService
    ): void {
        Gate::authorize('personnels.merge');

        $personnel = Personnel::findOrFail($personnelId);
        $mergeService->removeSuspicious($personnel);

        session()->flash(
            'success',
            'Record mencurigakan telah dilepas dari relasi SPT dan dihapus dari daftar personil aktif.'
        );
    }

    public function render(PersonnelMergeService $mergeService)
    {
        $duplicateGroups = $this->duplicateGroups($mergeService);
        $suspicious = $this->suspiciousPersonnel($mergeService);

        foreach ($duplicateGroups as $key => $group) {
            if (! isset($this->primarySelections[$key])) {
                $preferred = $group
                    ->sortByDesc(fn (Personnel $personnel) => filled($personnel->nip))
                    ->sortByDesc('letters_count')
                    ->first();

                if ($preferred) {
                    $this->primarySelections[$key] = $preferred->id;
                }
            }
        }

        return view('livewire.personnel-duplicates.index', [
            'duplicateGroups' => $duplicateGroups,
            'suspiciousPersonnel' => $suspicious,
            'manualPrimaryResults' => $this->manualSearchResults(
                $this->manualPrimarySearch
            ),
            'manualDuplicateResults' => $this->manualSearchResults(
                $this->manualDuplicateSearch,
                $this->manualPrimaryId
            ),
        ]);
    }

    private function duplicateGroups(
        PersonnelMergeService $mergeService
    ): Collection {
        $personnels = $this->baseQuery()->get();

        return $personnels
            ->groupBy(fn (Personnel $personnel) => $mergeService->normalizeName($personnel->name))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->sortByDesc(fn (Collection $group) => $group->count());
    }

    private function suspiciousPersonnel(
        PersonnelMergeService $mergeService
    ): Collection {
        return $this->baseQuery()
            ->get()
            ->filter(fn (Personnel $personnel) => $mergeService->isSuspiciousName($personnel->name))
            ->values();
    }


    private function manualSearchResults(
        string $search,
        ?int $excludeId = null
    ): Collection {
        $search = trim($search);

        if (mb_strlen($search) < 2) {
            return collect();
        }

        return Personnel::query()
            ->with('unit')
            ->withCount('letters')
            ->when(
                $excludeId,
                fn ($query) => $query->whereKeyNot($excludeId)
            )
            ->where(function ($query) use ($search) {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nip', 'ilike', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get();
    }

    private function baseQuery()
    {
        return Personnel::query()
            ->with('unit')
            ->withCount('letters')
            ->when(
                filled($this->search),
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('nip', 'ilike', "%{$search}%");
                    });
                }
            )
            ->orderBy('name');
    }
}
