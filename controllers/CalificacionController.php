<?php
/**
 * Controlador de Calificaciones
 * GradeBook RA
 */

require_once 'models/Calificacion.php';
require_once 'models/RA.php';
require_once 'models/Estudiante.php';

class CalificacionController {
    private $conexion;
    private $calificacionModel;
    private $raModel;
    private $estudianteModel;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->calificacionModel = new Calificacion($conexion);
        $this->raModel = new RA($conexion);
        $this->estudianteModel = new Estudiante($conexion);
    }
    
    /**
     * Mostrar libro de calificaciones
     */
    public function libro() {
        verificarAutenticacion();
        
        $raId = $_GET['ra_id'] ?? null;
        $cursoId = $_GET['curso_id'] ?? null;
        
        if (!$raId || !$cursoId) {
            mostrarMensaje('error', 'RA o Curso no especificado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        // Obtener RA
        $ra = $this->raModel->obtenerRADetalle($raId);
        if (!$ra) {
            mostrarMensaje('error', 'RA no encontrado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        // Obtener actividades del RA
        $actividades = $this->raModel->obtenerActividadesRA($raId);
        
        // Obtener estudiantes del curso
        $estudiantes = $this->estudianteModel->obtenerEstudiantesPorCurso($cursoId);
        
        // Obtener calificaciones
        $calificaciones = $this->obtenerMatrizCalificaciones($raId, $cursoId);
        
        $datos = [
            'ra' => $ra,
            'actividades' => $actividades,
            'estudiantes' => $estudiantes,
            'calificaciones' => $calificaciones,
            'curso_id' => $cursoId
        ];
        
        renderizarVista('calificaciones/libro', $datos);
    }
    
    /**
     * Obtener matriz de calificaciones
     */
    private function obtenerMatrizCalificaciones($raId, $cursoId) {
        $raId = (int)$raId;
        $cursoId = (int)$cursoId;
        
        $query = "SELECT 
                    cal.id,
                    cal.estudiante_id,
                    cal.actividad_id,
                    cal.puntos_obtenidos,
                    cal.nota_final,
                    cal.estado
                  FROM calificaciones cal
                  JOIN actividades a ON cal.actividad_id = a.id
                  JOIN resultados_aprendizaje ra ON a.ra_id = ra.id
                  WHERE ra.id = ? AND ra.curso_id = ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$raId, $cursoId]);
        
        $matriz = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $clave = $row['estudiante_id'] . '_' . $row['actividad_id'];
            $matriz[$clave] = $row;
        }
        
        return $matriz;
    }
    
    /**
     * Guardar calificación
     */
    public function guardar() {
        verificarAutenticacion();
        
        $estudianteId = $_POST['estudiante_id'] ?? null;
        $actividadId = $_POST['actividad_id'] ?? null;
        $puntos = $_POST['puntos'] ?? 0;
        $observaciones = $_POST['observaciones'] ?? '';
        $raId = $_POST['ra_id'] ?? null;
        $cursoId = $_POST['curso_id'] ?? null;
        
        if (!$estudianteId || !$actividadId) {
            // AJAX request
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                      (!empty($_GET['ajax']));
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit();
            }
            mostrarMensaje('error', 'Datos incompletos');
            exit();
        }
        
        $datos = [
            'estudiante_id' => $estudianteId,
            'actividad_id' => $actividadId,
            'puntos_obtenidos' => $puntos,
            'observaciones' => $observaciones
        ];
        
        $success = $this->calificacionModel->guardar($datos);
        
        // Check if this is an AJAX request
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (!empty($_GET['ajax']));
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Calificación guardada' : 'Error al guardar la calificación'
            ]);
            exit();
        }
        
        if ($success) {
            mostrarMensaje('exito', 'Calificación guardada');
        } else {
            mostrarMensaje('error', 'Error al guardar la calificación');
        }
        
        header("Location: index.php?pagina=libro_calificaciones&ra_id=$raId&curso_id=$cursoId");
        exit();
    }
    
    /**
     * Ver calificaciones de un estudiante
     */
    public function verEstudiante() {
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
        
        // Obtener calificaciones
        $calificaciones = $this->calificacionModel->obtenerCalificacionesEstudiante($estudianteId);
        
        // Obtener promedios por RA
        $promedios = $this->calificacionModel->obtenerPromediosPorRA($estudianteId);
        
        // Calcular nota final ponderada
        $notaFinal = $this->calcularNotaFinalPonderada($promedios);
        
        $datos = [
            'estudiante' => $estudiante,
            'calificaciones' => $calificaciones,
            'promedios' => $promedios,
            'nota_final' => $notaFinal
        ];
        
        renderizarVista('calificaciones/estudiante', $datos);
    }
    
    /**
     * Calcular nota final ponderada
     */
    private function calcularNotaFinalPonderada($promedios) {
        $notaFinal = 0;
        
        foreach ($promedios as $promedio) {
            if ($promedio['promedio_ra']) {
                $notaFinal += ($promedio['promedio_ra'] * $promedio['porcentaje_ponderacion'] / 100);
            }
        }
        
        return round($notaFinal, 2);
    }
}

?><?php
// Archivo actualizado para subir a repositorio
/**
 * Controlador de Calificaciones
 * GradeBook RA
 */

require_once 'models/Calificacion.php';
require_once 'models/RA.php';
require_once 'models/Estudiante.php';

class CalificacionController {
    private $conexion;
    private $calificacionModel;
    private $raModel;
    private $estudianteModel;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->calificacionModel = new Calificacion($conexion);
        $this->raModel = new RA($conexion);
        $this->estudianteModel = new Estudiante($conexion);
    }
    
    /**
     * Mostrar libro de calificaciones
     */
    public function libro() {
        verificarAutenticacion();
        
        $raId = $_GET['ra_id'] ?? null;
        $cursoId = $_GET['curso_id'] ?? null;
        
        if (!$raId || !$cursoId) {
            mostrarMensaje('error', 'RA o Curso no especificado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        // Obtener RA
        $ra = $this->raModel->obtenerRADetalle($raId);
        if (!$ra) {
            mostrarMensaje('error', 'RA no encontrado');
            header("Location: index.php?pagina=dashboard");
            exit();
        }
        
        // Obtener actividades del RA
        $actividades = $this->raModel->obtenerActividadesRA($raId);
        
        // Obtener estudiantes del curso
        $estudiantes = $this->estudianteModel->obtenerEstudiantesPorCurso($cursoId);
        
        // Obtener calificaciones
        $calificaciones = $this->obtenerMatrizCalificaciones($raId, $cursoId);
        
        $datos = [
            'ra' => $ra,
            'actividades' => $actividades,
            'estudiantes' => $estudiantes,
            'calificaciones' => $calificaciones,
            'curso_id' => $cursoId
        ];
        
        renderizarVista('calificaciones/libro', $datos);
    }
    
    /**
     * Obtener matriz de calificaciones
     */
    private function obtenerMatrizCalificaciones($raId, $cursoId) {
        $raId = (int)$raId;
        $cursoId = (int)$cursoId;
        
        $query = "SELECT 
                    cal.id,
                    cal.estudiante_id,
                    cal.actividad_id,
                    cal.puntos_obtenidos,
                    cal.nota_final,
                    cal.estado
                  FROM calificaciones cal
                  JOIN actividades a ON cal.actividad_id = a.id
                  JOIN resultados_aprendizaje ra ON a.ra_id = ra.id
                  WHERE ra.id = ? AND ra.curso_id = ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$raId, $cursoId]);
        
        $matriz = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $clave = $row['estudiante_id'] . '_' . $row['actividad_id'];
            $matriz[$clave] = $row;
        }
        
        return $matriz;
    }
    
    /**
     * Guardar calificación
     */
    public function guardar() {
        verificarAutenticacion();
        
        $estudianteId = $_POST['estudiante_id'] ?? null;
        $actividadId = $_POST['actividad_id'] ?? null;
        $puntos = $_POST['puntos'] ?? 0;
        $observaciones = $_POST['observaciones'] ?? '';
        $raId = $_POST['ra_id'] ?? null;
        $cursoId = $_POST['curso_id'] ?? null;
        
        if (!$estudianteId || !$actividadId) {
            // AJAX request
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                      (!empty($_GET['ajax']));
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit();
            }
            mostrarMensaje('error', 'Datos incompletos');
            exit();
        }
        
        $datos = [
            'estudiante_id' => $estudianteId,
            'actividad_id' => $actividadId,
            'puntos_obtenidos' => $puntos,
            'observaciones' => $observaciones
        ];
        
        $success = $this->calificacionModel->guardar($datos);
        
        // Check if this is an AJAX request
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (!empty($_GET['ajax']));
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Calificación guardada' : 'Error al guardar la calificación'
            ]);
            exit();
        }
        
        if ($success) {
            mostrarMensaje('exito', 'Calificación guardada');
        } else {
            mostrarMensaje('error', 'Error al guardar la calificación');
        }
        
        header("Location: index.php?pagina=libro_calificaciones&ra_id=$raId&curso_id=$cursoId");
        exit();
    }
    
    /**
     * Ver calificaciones de un estudiante
     */
    public function verEstudiante() {
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
        
        // Obtener calificaciones
        $calificaciones = $this->calificacionModel->obtenerCalificacionesEstudiante($estudianteId);
        
        // Obtener promedios por RA
        $promedios = $this->calificacionModel->obtenerPromediosPorRA($estudianteId);
        
        // Calcular nota final ponderada
        $notaFinal = $this->calcularNotaFinalPonderada($promedios);
        
        $datos = [
            'estudiante' => $estudiante,
            'calificaciones' => $calificaciones,
            'promedios' => $promedios,
            'nota_final' => $notaFinal
        ];
        
        renderizarVista('calificaciones/estudiante', $datos);
    }
    
    /**
     * Calcular nota final ponderada
     */
    private function calcularNotaFinalPonderada($promedios) {
        $notaFinal = 0;
        
        foreach ($promedios as $promedio) {
            if ($promedio['promedio_ra']) {
                $notaFinal += ($promedio['promedio_ra'] * $promedio['porcentaje_ponderacion'] / 100);
            }
        }
        
        return round($notaFinal, 2);
    }
}

?>
