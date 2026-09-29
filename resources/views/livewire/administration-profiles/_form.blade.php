<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-5">
        <h2 class="font-bold text-slate-900">Identitas Kop Surat</h2>
        <p class="mt-1 text-xs text-slate-500">
            Digunakan sebagai identitas instansi pada preview dan dokumen surat.
        </p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <label class="grid gap-1.5 text-sm font-medium">
            <span>Nama Profil *</span>
            <input wire:model="name" class="rounded-xl border-slate-200">
            @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>Nama Instansi *</span>
            <input wire:model="organization_name" class="rounded-xl border-slate-200">
            @error('organization_name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="grid gap-1.5 text-sm font-medium md:col-span-2">
            <span>Instansi Induk</span>
            <input wire:model="parent_organization" class="rounded-xl border-slate-200">
        </label>

        <label class="grid gap-1.5 text-sm font-medium md:col-span-2">
            <span>Alamat</span>
            <textarea wire:model="address" rows="3" class="rounded-xl border-slate-200"></textarea>
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>Telepon</span>
            <input wire:model="phone" class="rounded-xl border-slate-200">
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>Email</span>
            <input wire:model="email" type="email" class="rounded-xl border-slate-200">
            @error('email')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>Website</span>
            <input wire:model="website" class="rounded-xl border-slate-200">
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>Kota Surat</span>
            <input wire:model="city" class="rounded-xl border-slate-200" placeholder="Contoh: Denpasar">
        </label>

        <label class="grid gap-1.5 text-sm font-medium md:col-span-2">
            <span>Logo</span>
            <input wire:model="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="rounded-xl border border-slate-200 p-3 text-sm">
            <span class="text-xs font-normal text-slate-500">JPG, PNG, atau WebP maksimal 2 MB.</span>
            @error('logo')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-5">
        <h2 class="font-bold text-slate-900">Penandatangan Default</h2>
        <p class="mt-1 text-xs text-slate-500">
            Data ini menjadi default dan dapat digunakan sebagai placeholder template.
        </p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <label class="grid gap-1.5 text-sm font-medium">
            <span>Nama Penandatangan</span>
            <input wire:model="signatory_name" class="rounded-xl border-slate-200">
        </label>

        <label class="grid gap-1.5 text-sm font-medium">
            <span>NIP</span>
            <input wire:model="signatory_nip" class="rounded-xl border-slate-200">
        </label>

        <label class="grid gap-1.5 text-sm font-medium md:col-span-2">
            <span>Jabatan</span>
            <input wire:model="signatory_position" class="rounded-xl border-slate-200">
        </label>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap gap-6">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_default" class="rounded border-slate-300">
            Jadikan profil kop default
        </label>

        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300">
            Profil aktif
        </label>
    </div>
</section>
