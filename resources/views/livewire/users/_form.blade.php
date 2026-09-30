<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-1.5 text-sm font-medium">
        <span>Nama *</span>
        <input wire:model="name" class="rounded-xl border-slate-200">
        @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </label>

    <label class="grid gap-1.5 text-sm font-medium">
        <span>Email *</span>
        <input wire:model="email" type="email" class="rounded-xl border-slate-200">
        @error('email')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </label>

    <label class="grid gap-1.5 text-sm font-medium">
        <span>Personil</span>
        <select wire:model="personnel_id" class="rounded-xl border-slate-200">
            <option value="">Tidak ditautkan</option>
            @foreach($personnels as $person)
                <option value="{{ $person->id }}">
                    {{ $person->name }}{{ $person->nip ? ' — '.$person->nip : '' }}
                </option>
            @endforeach
        </select>
        @error('personnel_id')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </label>

    <label class="grid gap-1.5 text-sm font-medium">
        <span>Role *</span>
        <select wire:model="role" class="rounded-xl border-slate-200">
            <option value="">Pilih role</option>
            @foreach($roles as $item)
                <option value="{{ $item->name }}">{{ $item->name }}</option>
            @endforeach
        </select>
        @error('role')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </label>
</div>
