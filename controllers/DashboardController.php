<?php
/**
 * Controlador de Dashboard
 * GradeBook RA
 */

require_once 'models/Curso.php';

class DashboardController {
    private $conexion;
    private $cursoModel;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->cursoModel = new Curso($conexion);
    }
    
    /**
     * Mostrar dashboard principal
     */
    public function mostrar() {
        verificarAutenticacion();
        
        $usuarioId = obtenerUsuarioActual();
        $cursos = $this->cursoModel->obtenerCursosPorUsuario($usuarioId);
        
        // Calcular estadísticas generales
        $estadisticasGenerales = $this->obtenerEstadisticasGenerales($usuarioId);
        
        $datos = [
            'cursos' => $cursos,
            'estadisticas' => $estadisticasGenerales,
            'usuario_nombre' => obtenerNombreUsuario()
        ];
        
        renderizarVista('dashboard/principal', $datos);
    }
    
    /**
     * Obtener estadísticas generales del docente
     */
    private function obtenerEstadisticasGenerales($usuarioId) {
        $usuarioId = (int)$usuarioId;
        
        $query = "SELECT 
                    COUNT(DISTINCT m.id) as total_materias,
                    COUNT(DISTINCT c.id) as total_cursos,
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    ROUND(AVG(e.nota_final), 2) as promedio_general
                  FROM materias m
                  LEFT JOIN cursos c ON m.id = c.materia_id
                  LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.estado = 'activo'
                  WHERE m.usuario_id = ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$usuarioId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: [
            'total_materias' => 0,
            'total_cursos' => 0,
            'total_estudiantes' => 0,
            'promedio_general' => 0
        ];
    }
}

?>