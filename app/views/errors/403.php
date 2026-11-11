<?php
/**
 * Halaman 403 — akses ditolak.
 */

$role = auth_role();
?>
<div class="clay-panel text-center mx-auto" style="max-width: 540px;">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></div>
        <h2 class="mb-2">Akses Ditolak</h2>
        <p class="mb-4">
            Akun Anda tidak memiliki izin untuk membuka halaman ini.
            Silakan kembali ke dashboard sesuai peran akun Anda.
        </p>
        <a class="btn btn-primary-clay"
           href="<?= e(url(dashboard_path_for((string) $role))) ?>">
            <i class="bi bi-speedometer2" aria-hidden="true"></i> Kembali ke Dashboard
        </a>
    </div>
</div>
