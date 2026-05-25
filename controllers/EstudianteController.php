<?php
/**
 * Controlador de Estudiantes
 * GradeBook RA
 */

require_once 'models/Estudiante.php';
require_once 'models/Curso.php';

class EstudianteController {
    private $conexion;
    private $estudianteModel;
    private $cursoModel;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->estudianteModel = new Estudiante($conexion);
        $this->cursoModel = new Curso($conexion);
    }
    
    /**
     * Listar estudiantes de un curso
     */
    public function listar() {
        verificarAutenticacion();
        
        $cursoId = $_GET['curso_id'] ?? null;
        $filtro = $_GET['filtro'] ?? '';
        
        if (!$cursoId) {
            mostrarMensaje('error', 'Curso no especificado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        $curso = $this->cursoModel->obtenerCursoDetalle($cursoId);
        if (!$curso) {
            mostrarMensaje('error', 'Curso no encontrado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        $estudiantes = $this->estudianteModel->obtenerEstudiantesPorCurso($cursoId, $filtro);
        
        $datos = [
            'curso' => $curso,
            'estudiantes' => $estudiantes,
            'filtro' => $filtro
        ];
        
        renderizarVista('estudiantes/listar', $datos);
    }
    
    /**
     * Ver detalle de estudiante
     */
    public function ver() {
        verificarAutenticacion();
        
        $estudianteId = $_GET['id'] ?? null;
        
        if (!$estudianteId) {
            mostrarMensaje('error', 'Estudiante no especificado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        $estudiante = $this->estudianteModel->obtenerDetalleEstudiante($estudianteId);
        
        if (!$estudiante) {
            mostrarMensaje('error', 'Estudiante no encontrado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        $datos = [
            'estudiante' => $estudiante
        ];
        
        renderizarVista('estudiantes/detalle', $datos);
    }
    
    /**
     * Crear nuevo estudiante
     */
    public function crear() {
        verificarAutenticacion();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $cursoId = $_GET['curso_id'] ?? null;
            
            if ($cursoId) {
                $curso = $this->cursoModel->obtenerCursoDetalle($cursoId);
                $datos = ['curso' => $curso];
                renderizarVista('estudiantes/crear', $datos);
            } else {
                mostrarMensaje('error', 'Curso no especificado');
                header("Location: index.php?pagina=dashboard");
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarCrear();
        }
    }
    
    /**
     * Procesar creación de estudiante
     */
    private function procesarCrear() {
        $cedula = $_POST['cedula'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $cursoId = $_POST['curso_id'] ?? '';
        $genero = $_POST['genero'] ?? 'M';
        
        // Validaciones
        if (empty($cedula) || empty($apellido) || empty($nombre)) {
            mostrarMensaje('error', 'Todos los campos requeridos deben ser completados');
            return $this->crear();
        }
        
        if ($this->estudianteModel->cedulaExiste($cedula)) {
            mostrarMensaje('error', 'La cédula ya está registrada');
            return $this->crear();
        }
        
        $datos = [
            'cedula' => $cedula,
            'apellido' => $apellido,
            'nombre' => $nombre,
            'email' => $email,
            'curso_id' => $cursoId,
            'genero' => $genero
        ];
        
        if ($this->estudianteModel->crear($datos)) {
            mostrarMensaje('exito', 'Estudiante creado correctamente');
            header("Location: index.php?pagina=estudiantes&curso_id=$cursoId");
            exit();
        } else {
            mostrarMensaje('error', 'Error al crear el estudiante');
            return $this->crear();
        }
    }
    
    /**
     * Editar estudiante
     */
    public function editar() {
        verificarAutenticacion();
        
        $estudianteId = $_GET['id'] ?? null;
        
        if (!$estudianteId) {
            mostrarMensaje('error', 'Estudiante no especificado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $estudiante = $this->estudianteModel->obtenerDetalleEstudiante($estudianteId);
            
            if (!$estudiante) {
                mostrarMensaje('error', 'Estudiante no encontrado');
                header("Location: index.php?pagina=dashboard");
                exit();
            }
            
            $datos = ['estudiante' => $estudiante];
            renderizarVista('estudiantes/editar', $datos);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarEditar($estudianteId);
        }
    }
    
    /**
     * Procesar edición de estudiante
     */
    private function procesarEditar($estudianteId) {
        $apellido = $_POST['apellido'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $genero = $_POST['genero'] ?? 'M';
        
        if (empty($apellido) || empty($nombre)) {
            mostrarMensaje('error', 'Apellido y nombre son requeridos');
            return $this->editar();
        }
        
        $datos = [
            'apellido' => $apellido,
            'nombre' => $nombre,
            'email' => $email,
            'genero' => $genero
        ];
        
        if ($this->estudianteModel->actualizar($estudianteId, $datos)) {
            $estudiante = $this->estudianteModel->obtenerDetalleEstudiante($estudianteId);
            mostrarMensaje('exito', 'Estudiante actualizado correctamente');
            header("Location: index.php?pagina=estudiante&id=$estudianteId");
            exit();
        } else {
            mostrarMensaje('error', 'Error al actualizar el estudiante');
            return $this->editar();
        }
    }
    
    /**
     * Retirar estudiante
     */
    public function retirar() {
        verificarAutenticacion();
        
        $estudianteId = $_POST['id'] ?? null;
        $cursoId = $_POST['curso_id'] ?? null;
        
        if (!$estudianteId) {
            mostrarMensaje('error', 'Estudiante no especificado');
            exit();
        }
        
        if ($this->estudianteModel->retirar($estudianteId)) {
            mostrarMensaje('exito', 'Estudiante retirado correctamente');
        } else {
            mostrarMensaje('error', 'Error al retirar el estudiante');
        }
        
        header("Location: index.php?pagina=estudiantes&curso_id=$cursoId");
        exit();
    }
}

?>