<?php
/**
 * Controlador de Autenticación
 * GradeBook RA
 */

require_once 'models/Usuario.php';

class AuthController {
    private $conexion;
    private $usuarioModel;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->usuarioModel = new Usuario($conexion);
    }
    
    /**
     * Mostrar formulario de login
     */
    public function mostrarLogin() {
        renderizarVista('auth/login');
    }
    
    /**
     * Procesar login
     */
    public function procesarLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                mostrarMensaje('error', 'Email y contraseña son requeridos');
                return $this->mostrarLogin();
            }
            
            $usuario = $this->usuarioModel->autenticar($email, $password);
            
            if ($usuario) {
                iniciarSesion();
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_cargo'] = $usuario['cargo'];
                
                mostrarMensaje('exito', '¡Bienvenido! ' . $usuario['nombre']);
                header("Location: index.php?pagina=dashboard");
                exit();
            } else {
                mostrarMensaje('error', 'Email o contraseña incorrectos');
                return $this->mostrarLogin();
            }
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        iniciarSesion();
        session_destroy();
        mostrarMensaje('exito', 'Sesión cerrada correctamente');
        header("Location: index.php?pagina=login");
        exit();
    }
}

?>