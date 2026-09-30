<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\AuditService;

new #[Title('Profil Saya')] class extends Component {
    use ProfileValidationRules, WithFileUploads;

    public array $employment = [];
    public $photo;

    public function updateEmployment(AuditService $audit): void
    {
        $person = Auth::user()->personnel()->first();
        abort_unless($person, 403);
        $data = $this->validate([
            'employment.name' => ['required', 'string', 'max:255'],
            'employment.nip' => ['nullable', 'string', 'max:50', Rule::unique('personnels', 'nip')->ignore($person->id)],
            'employment.unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'employment.rank' => ['nullable', 'string', 'max:100'],
            'employment.grade' => ['nullable', 'string', 'max:50'],
            'employment.position' => ['nullable', 'string', 'max:255'],
            'employment.email' => ['nullable', 'email', 'max:255'],
            'employment.phone' => ['nullable', 'string', 'max:30'],
        ])['employment'];
        $data = array_intersect_key($data, array_flip(['name', 'nip', 'unit_id', 'rank', 'grade', 'position', 'email', 'phone']));
        foreach ($data as $key => $value) {
            $data[$key] = is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
        }
        $old = $person->getOriginal();
        \Illuminate\Support\Facades\DB::transaction(function () use ($person, $data, $old, $audit) {
            $person->update($data);
            $audit->updated($person, $old);
        });
        Auth::user()->unsetRelation('personnel');
        session()->flash('employment-saved', 'Data kepegawaian berhasil diperbarui.');
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096']]);
    }

    public function savePhoto(): void
    {
        $this->updatedPhoto();
        $user = Auth::user();
        $old = $user->profile_photo_path;
        $path = $this->photo->store('profile-photos/'.$user->id, 'local');
        if (! $path) {
            $this->addError('photo', 'Foto gagal disimpan. Silakan coba kembali.');
            return;
        }
        try {
            $user->forceFill(['profile_photo_path' => $path])->save();
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        if ($old) Storage::disk('local')->delete($old);
        $this->reset('photo');
        session()->flash('photo-saved', 'Foto profil berhasil diperbarui.');
        $this->redirectRoute('profile.edit', navigate: true);
    }

    public function removePhoto(): void
    {
        $user = Auth::user();
        $old = $user->profile_photo_path;
        $user->forceFill(['profile_photo_path' => null])->save();
        if ($old) Storage::disk('local')->delete($old);
        $this->reset('photo');
        session()->flash('photo-saved', 'Foto profil berhasil dihapus.');
        $this->redirectRoute('profile.edit', navigate: true);
    }

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->employment = Auth::user()->personnel?->only(['name', 'nip', 'unit_id', 'rank', 'grade', 'position', 'email', 'phone']) ?? [];
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        session()->flash('profile-saved', 'Informasi akun berhasil diperbarui.');
        $this->redirectRoute('profile.edit', navigate: true);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="settings-page">
    @include('partials.settings-heading')
    <x-pages::settings.layout heading="Profil Saya" subheading="Kelola foto, informasi akun, dan data kepegawaian Anda.">
        <form wire:submit="savePhoto" class="settings-section">
            <h3>Foto Profil</h3><p class="portal-caption">Gunakan foto yang jelas agar akun Anda mudah dikenali.</p>
            <div class="settings-photo-row">@if($photo && ! $errors->has('photo'))<span class="app-avatar settings-avatar"><img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto profil baru" /></span>@else<x-app.avatar class="settings-avatar" />@endif<div class="min-w-0 flex-1">
                <flux:input type="file" wire:model="photo" label="Pilih foto baru" accept="image/jpeg,image/png,image/webp" />
                <p class="portal-caption mt-2">JPG, PNG, atau WebP. Maksimal 2 MB dan 4096 &times; 4096 piksel.</p>
                <p wire:loading wire:target="photo" class="portal-caption mt-2">Mengunggah foto...</p>
            </div></div>
            @if($photo && ! $errors->has('photo'))<p class="portal-caption mt-3">Foto dipilih: {{ $photo->getClientOriginalName() }}</p>@endif
            <div class="settings-actions"><flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="photo,savePhoto">Simpan Foto</flux:button>@if(auth()->user()->profile_photo_path)<flux:button type="button" wire:click="removePhoto" wire:confirm="Hapus foto profil Anda?">Hapus Foto</flux:button>@endif</div>
            @if(session('photo-saved'))<p role="status" class="settings-success">{{ session('photo-saved') }}</p>@endif
        </form>
        <form wire:submit="updateProfileInformation" class="settings-section">
            <h3>Informasi Akun</h3><p class="portal-caption">Nama tampilan dan email yang digunakan untuk masuk ke aplikasi.</p>
            <div class="settings-fields"><flux:input wire:model="name" label="Nama Akun" required autocomplete="name" /><flux:input wire:model="email" label="Email Akun" type="email" required autocomplete="email" /></div>
            @if($this->hasUnverifiedEmail)<div class="settings-notice">Email Anda belum terverifikasi. <button type="button" wire:click="resendVerificationNotification" class="portal-text-link">Kirim ulang email verifikasi</button>@if(session('status') === 'verification-link-sent')<p role="status">Tautan verifikasi baru telah dikirim.</p>@endif</div>@endif
            <div class="settings-actions"><flux:button variant="primary" type="submit" data-test="update-profile-button">Simpan Informasi Akun</flux:button></div>
            @if(session('profile-saved'))<p role="status" class="settings-success">{{ session('profile-saved') }}</p>@endif
        </form>
        <section class="settings-section">
            <h3>Data Kepegawaian / Personil</h3><p class="portal-caption">Data ini digunakan pada administrasi surat dan penugasan Anda.</p>
            @if(auth()->user()->personnel)
            <form wire:submit="updateEmployment">
                <div class="settings-fields">
                    <flux:input wire:model="employment.name" label="Nama Lengkap Personil" required />
                    <flux:input wire:model="employment.nip" label="NIP" />
                    <flux:select wire:model="employment.unit_id" label="Unit / Tim Kerja"><option value="">Belum ditentukan</option>@foreach(\App\Models\Unit::orderBy('name')->get() as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</flux:select>
                    <flux:input wire:model="employment.position" label="Jabatan" />
                    <flux:input wire:model="employment.rank" label="Pangkat" />
                    <flux:input wire:model="employment.grade" label="Golongan" />
                    <flux:input wire:model="employment.email" label="Email Kepegawaian" type="email" />
                    <flux:input wire:model="employment.phone" label="Nomor Telepon" type="tel" />
                </div>
                <div class="settings-actions"><flux:button variant="primary" type="submit">Simpan Data Kepegawaian</flux:button></div>
                @if(session('employment-saved'))<p role="status" class="settings-success">{{ session('employment-saved') }}</p>@endif
            </form>
            @else<div class="settings-notice">Akun Anda belum terhubung ke data personil. Hubungi administrator untuk menautkan data kepegawaian Anda.</div>@endif
        </section>
        @if($this->showDeleteUser)<div class="settings-section settings-danger"><livewire:pages::settings.delete-user-form /></div>@endif
    </x-pages::settings.layout>
</section>
