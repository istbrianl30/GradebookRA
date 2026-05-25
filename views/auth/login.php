<?php include 'views/layout/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="h5 mb-0"><i class="fas fa-school text-primary"></i> GradeBook RA</h2>
                        <p class="text-muted mb-0">Iniciar sesión</p>
                    </div>
                    
                    <?php
                    $mensaje = obtenerYLimpiarMensaje();
                    if ($mensaje):
                        $clase = $mensaje['tipo'] === 'exito' ? 'alert-success' : 'alert-danger';
                    ?>
                        <div class="alert <?php echo $clase; ?> alert-dismissible fade show mb-4" role="alert">
                            <?php echo $mensaje['contenido']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="index.php?pagina=login">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email institucional</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="tu.email@institucion.edu.co">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="••••••••">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg py-2">
                                <i class="fas fa-sign-in-alt me-2"></i> Ingresar
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                ¿Problemas para acceder? Contacta al administrador del sistema.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>