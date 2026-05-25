<?php
/**
 * Archivo Principal - Punto de Entrada
 * GradeBook RA - Sistema de Gestión Académica
 */

// Incluir configuración y funciones
require_once 'config/database.php';
require_once 'config/funciones.php';

// Incluir controladores
require_once 'controllers/AuthController.php';
require_once 'controllers/DashboardController.php';
require_once 'controllers/EstudianteController.php';
require_once 'controllers/CalificacionController.php';

// Iniciar sesión
iniciarSesion();

// Obtener página solicitada
$pagina = $_GET['pagina'] ?? 'login';

// Enrutador
switch ($pagina) {
    // Autenticación
    case 'login':
        $authController = new AuthController($conn);
        $authController->procesarLogin();
        $authController->mostrarLogin();
        break;
        
    case 'logout':
        $authController = new AuthController($conn);
        $authController->logout();
        break;
    
    // Dashboard
    case 'dashboard':
        $dashboardController = new DashboardController($conn);
        $dashboardController->mostrar();
        break;
    
    // Estudiantes
    case 'estudiantes':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->listar();
        break;
    
    case 'estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->ver();
        break;
    
    case 'crear_estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->crear();
        break;
    
    case 'editar_estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->editar();
        break;
    
    case 'retirar_estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->retirar();
        break;
    
    // Calificaciones
    case 'libro_calificaciones':
        $calificacionController = new CalificacionController($conn);
        $calificacionController->libro();
        break;
    
    case 'calificaciones_estudiante':
        $calificacionController = new CalificacionController($conn);
        $calificacionController->verEstudiante();
        break;
    
    case 'guardar_calificacion':
        $calificacionController = new CalificacionController($conn);
        $calificacionController->guardar();
        break;
    
    default:
        if (estaAutenticado()) {
            $dashboardController = new DashboardController($conn);
            $dashboardController->mostrar();
        } else {
            $authController = new AuthController($conn);
            $authController->mostrarLogin();
        }
        break;
}

// Cerrar conexión
$conn = null;

?>
<?php
// Archivo actualizado para subir a repositorio
/**
 * Archivo Principal - Punto de Entrada
 * GradeBook RA - Sistema de Gestión Académica
 */

// Incluir configuración y funciones
require_once 'config/database.php';
require_once 'config/funciones.php';

// Incluir controladores
require_once 'controllers/AuthController.php';
require_once 'controllers/DashboardController.php';
require_once 'controllers/EstudianteController.php';
require_once 'controllers/CalificacionController.php';

// Iniciar sesión
iniciarSesion();

// Obtener página solicitada
$pagina = $_GET['pagina'] ?? 'login';

// Enrutador
switch ($pagina) {
    // Autenticación
    case 'login':
        $authController = new AuthController($conn);
        $authController->procesarLogin();
        $authController->mostrarLogin();
        break;
        
    case 'logout':
        $authController = new AuthController($conn);
        $authController->logout();
        break;
    
    // Dashboard
    case 'dashboard':
        $dashboardController = new DashboardController($conn);
        $dashboardController->mostrar();
        break;
    
    // Estudiantes
    case 'estudiantes':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->listar();
        break;
    
    case 'estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->ver();
        break;
    
    case 'crear_estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->crear();
        break;
    
    case 'editar_estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->editar();
        break;
    
    case 'retirar_estudiante':
        $estudianteController = new EstudianteController($conn);
        $estudianteController->retirar();
        break;
    
    // Calificaciones
    case 'libro_calificaciones':
        $calificacionController = new CalificacionController($conn);
        $calificacionController->libro();
        break;
    
    case 'calificaciones_estudiante':
        $calificacionController = new CalificacionController($conn);
        $calificacionController->verEstudiante();
        break;
    
    case 'guardar_calificacion':
        $calificacionController = new CalificacionController($conn);
        $calificacionController->guardar();
        break;
    
    default:
        if (estaAutenticado()) {
            $dashboardController = new DashboardController($conn);
            $dashboardController->mostrar();
        } else {
            $authController = new AuthController($conn);
            $authController->mostrarLogin();
        }
        break;
}

// Cerrar conexión
$conn = null;

?>
