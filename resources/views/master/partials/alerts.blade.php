@if(session('success'))
    <div class="crud-toast success" data-crud-toast role="status" aria-live="polite">
        <span class="crud-toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-9"/></svg></span>
        <div><strong>Berhasil</strong><p>{{ session('success') }}</p></div>
        <button type="button" data-toast-close aria-label="Tutup notifikasi">&times;</button>
        <button class="crud-toast-action" type="button" data-toast-close>Oke, selesai</button>
        <i class="crud-toast-progress"></i>
    </div>
@endif
@if(session('error'))
    <div class="crud-toast error" data-crud-toast role="alert" aria-live="assertive">
        <span class="crud-toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 8v5M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg></span>
        <div><strong>Tindakan gagal</strong><p>{{ session('error') }}</p></div>
        <button type="button" data-toast-close aria-label="Tutup notifikasi">&times;</button>
        <button class="crud-toast-action" type="button" data-toast-close>Coba lagi</button>
        <i class="crud-toast-progress"></i>
    </div>
@endif
@if($errors->any())
    <div class="master-alert error validation-alert" role="alert">
        <span class="validation-alert-icon">!</span><div><strong>Data belum dapat disimpan</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
