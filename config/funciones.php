<?php
/**
 * Funciones Globales y Utilidades
 * GradeBook RA
 */

/**
 * Función para iniciar sesión segura
 */
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function estaAutenticado() {
    iniciarSesion();
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nombre']);
}

/**
 * Redirigir a login si no está autenticado
 */
function verificarAutenticacion() {
    if (!estaAutenticado()) {
        header("Location: index.php?pagina=login");
        exit();
    }
}

/**
 * Obtener el ID del usuario actual
 */
function obtenerUsuarioActual() {
    iniciarSesion();
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Obtener nombre del usuario actual
 */
function obtenerNombreUsuario() {
    iniciarSesion();
    return $_SESSION['usuario_nombre'] ?? 'Invitado';
}

/**
 * Escapar datos para evitar inyección SQL (now using htmlspecialchars for display)
 */
function escapar($conexion, $datos) {
    if (is_array($datos)) {
        $resultado = array();
        foreach ($datos as $clave => $valor) {
            $resultado[$clave] = htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
        }
        return $resultado;
    }
    return htmlspecialchars($datos ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Convertir semáforo de desempeño
 */
function obtenerSemaforo($nota) {
    if ($nota >= 4.5) {
        return '<span class="badge badge-success">🟢 Excelente</span>';
    } elseif ($nota >= 3.0) {
        return '<span class="badge badge-warning">🟡 En Riesgo</span>';
    } else {
        return '<span class="badge badge-danger">🔴 Reprobado</span>';
    }
}

/**
 * Formatear número a 2 decimales
 */
function formatoNota($nota) {
    return number_format($nota, 2, '.', '');
}

/**
 * Redondear nota a escala
 */
function redondearNota($nota, $escala = 5.0) {
    $porcentaje = ($nota / $escala) * 100;
    $notaRedondeada = ($porcentaje / 100) * $escala;
    return round($notaRedondeada, 2);
}

/**
 * Convertir puntos a nota en escala
 */
function puntosANota($puntos, $puntosMaximos, $escala = 5.0) {
    if ($puntosMaximos == 0) return 0;
    $porcentaje = ($puntos / $puntosMaximos) * 100;
    $nota = ($porcentaje / 100) * $escala;
    return round($nota, 2);
}

/**
 * Obtener estado en texto
 */
function obtenerEstadoTexto($estado) {
    $estados = [
        'activo' => '<span class="badge badge-primary">Activo</span>',
        'inactivo' => '<span class="badge badge-secondary">Inactivo</span>',
        'retirado' => '<span class="badge badge-danger">Retirado</span>',
        'suspendido' => '<span class="badge badge-warning">Suspendido</span>',
        'finalizado' => '<span class="badge badge-info">Finalizado</span>',
    ];
    return $estados[$estado] ?? '<span class="badge badge-secondary">' . $estado . '</span>';
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generar contraseña hasheada
 */
function hashearPassword($password) {
    return hash('sha256', $password);
}

/**
 * Verificar contraseña
 */
function verificarPassword($password, $hash) {
    return hash('sha256', $password) === $hash;
}

/**
 * Obtener fecha formateada
 */
function formatoFecha($fecha, $formato = 'd/m/Y') {
    return date($formato, strtotime($fecha));
}

/**
 * Obtener fecha y hora formateada
 */
function formatoFechaHora($fechaHora, $formato = 'd/m/Y H:i') {
    return date($formato, strtotime($fechaHora));
}

/**
 * Mensaje de alerta (Flash)
 */
function mostrarMensaje($tipo, $mensaje) {
    iniciarSesion();
    $_SESSION['mensaje'] = [
        'tipo' => $tipo,
        'contenido' => $mensaje
    ];
}

/**
 * Obtener y limpiar mensaje
 */
function obtenerYLimpiarMensaje() {
    iniciarSesion();
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
        return $mensaje;
    }
    return null;
}

/**
 * Renderizar vista
 */
function renderizarVista($vista, $datos = []) {
    extract($datos);
    include "views/$vista.php";
}


