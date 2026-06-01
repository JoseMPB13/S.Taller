<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="card" style="max-width: 600px; margin: 4rem auto; text-align: center; padding: 3.5rem 2rem; border-top: 4px solid var(--danger);">
    <div style="font-size: 4rem; margin-bottom: 1rem; line-height: 1;">🚫</div>
    <h1 style="color: var(--danger); margin-bottom: 0.5rem; font-size: 2.2rem; font-weight: 700;">403 - Acceso Denegado</h1>
    <h2 style="color: var(--text-main); font-size: 1.25rem; margin-bottom: 1.5rem; font-weight: 500;">Permisos Insuficientes</h2>
    <p style="color: var(--text-muted); margin-bottom: 2.5rem; font-size: 1rem; line-height: 1.6; max-width: 480px; margin-left: auto; margin-right: auto;">
        Lo sentimos, no tiene privilegios suficientes para acceder a este módulo de administración. Si considera que esto es un error, por favor contacte al administrador del sistema.
    </p>
    <div>
        <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-primary">
            Volver al Panel Principal
        </a>
    </div>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
