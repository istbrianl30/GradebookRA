<?php include 'views/layout/header.php'; ?>

<div class="container-fluid mt-4">
    <!-- Mensaje Flash -->
    <?php
    $mensaje = obtenerYLimpiarMensaje();
    if ($mensaje):
        $clase = $mensaje['tipo'] === 'exito' ? 'alert-success' : 'alert-danger';
    ?>
        <div class="alert <?php echo $clase; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $mensaje['tipo'] === 'exito' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje['contenido']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0"><i class="fas fa-chart-line"></i> Dashboard</h1>
            <p class="text-muted small">Bienvenido, <?php echo obtenerNombreUsuario(); ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php?pagina=logout" class="btn btn-sm btn-danger">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </div>
    
    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-book text-primary" style="font-size: 30px;"></i>
                    <h5 class="card-title mt-3">Materias</h5>
                    <h3 class="card-text text-primary"><?php echo $estadisticas['total_materias'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-layer-group text-success" style="font-size: 30px;"></i>
                    <h5 class="card-title mt-3">Cursos</h5>
                    <h3 class="card-text text-success"><?php echo $estadisticas['total_cursos'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users text-info" style="font-size: 30px;"></i>
                    <h5 class="card-title mt-3">Estudiantes</h5>
                    <h3 class="card-text text-info"><?php echo $estadisticas['total_estudiantes'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-star text-warning" style="font-size: 30px;"></i>
                    <h5 class="card-title mt-3">Promedio</h5>
                    <h3 class="card-text text-warning"><?php echo formatoNota($estadisticas['promedio_general'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mis Cursos -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> Mis Cursos</h5>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($cursos)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <th>Sección</th>
                                        <th>Año</th>
                                        <th>Semestre</th>
                                        <th>Estudiantes</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cursos as $curso): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($curso['materia_nombre']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($curso['materia_codigo']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($curso['nombre_seccion']); ?></td>
                                            <td><?php echo htmlspecialchars($curso['anio_academico']); ?></td>
                                            <td><?php echo htmlspecialchars($curso['semestre']); ?></td>
                                            <td><span class="badge bg-info"><?php echo $curso['total_estudiantes']; ?></span></td>
                                            <td><?php echo obtenerEstadoTexto($curso['estado']); ?></td>
                                            <td>
                                                <a href="index.php?pagina=estudiantes&curso_id=<?php echo $curso['id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Ver Estudiantes">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <a href="index.php?pagina=libro_calificaciones&curso_id=<?php echo $curso['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Calificaciones">
                                                    <i class="fas fa-list"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay cursos asignados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>