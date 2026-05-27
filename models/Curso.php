<?php
/**
 * Modelo de Cursos
 * GradeBook RA
 */

class Curso {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    /**
     * Obtener cursos de un usuario (docente)
     */
    public function obtenerCursosPorUsuario($usuarioId) {
        $usuarioId = (int)$usuarioId;
        $query = "SELECT c.*, m.nombre as materia_nombre, m.codigo as materia_codigo 
                  FROM cursos c
                  JOIN materias m ON c.materia_id = m.id
                  WHERE m.usuario_id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener detalle de un curso
     */
    public function obtenerCursoDetalle($cursoId) {
        $cursoId = (int)$cursoId;
        $query = "SELECT c.*, m.nombre as materia_nombre, m.codigo as materia_codigo 
                  FROM cursos c
                  JOIN materias m ON c.materia_id = m.id
                  WHERE c.id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$cursoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}