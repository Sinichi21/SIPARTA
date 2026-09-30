@if(! auth()->user()->personnel_id)
    <div role="note" class="personnel-link-notice"><x-app.icon name="users" /><div><strong>Akun belum terhubung ke data personil</strong><p>Hubungi administrator untuk menautkan akun Anda agar surat tugas dan riwayat penugasan dapat ditampilkan.</p></div></div>
@endif
