<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Pengaturan Tampilan')] class extends Component {
    //
}; ?>

<section class="settings-page">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Pengaturan Tampilan') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Tampilan Aplikasi')" :subheading="__('Pilih tema yang nyaman digunakan pada perangkat ini.')">
        <div class="settings-section"><h3>Tema Aplikasi</h3><p class="portal-caption mb-5">Ikuti tema perangkat secara otomatis atau pilih tema pilihan Anda.</p><flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Terang') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Gelap') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('Sistem') }}</flux:radio>
        </flux:radio.group></div>
    </x-pages::settings.layout>
</section>
