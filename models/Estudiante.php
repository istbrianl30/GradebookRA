<?php
/**
 * Modelo de Estudiantes
 * GradeBook RA
 */

class Estudiante {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    /**
     * Obtener estudiantes de un curso
     */
    public function obtenerEstudiantesPorCurso($cursoId, $filtro = '') {
        $cursoId = (int)$cursoId;
        
        $where = "e.curso_id = ? AND e.estado = 'activo'";
        $params = [$cursoId];
        
        if (!empty($filtro)) {
            $where .= " AND (e.cedula LIKE ? OR e.apellido LIKE ? OR e.nombre LIKE ?)";
            $filtroParam = "%$filtro%";
            $params = array_merge($params, [$filtroParam, $filtroParam, $filtroParam]);
        }
        
        $query = "SELECT 
                    e.id,
                    e.cedula,
                    e.apellido,
                    e.nombre,
                    e.email,
                    e.genero,
                    e.estado,
                    e.nota_final,
                    e.asistencia_porcentaje
                  FROM estudiantes e
                  WHERE $where
                  ORDER BY e.apellido, e.nombre";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener detalle de estudiante
     */
    public function obtenerDetalleEstudiante($estudianteId) {
        $estudianteId = (int)$estudianteId;
        
        $query = "SELECT 
                    e.*,
                    c.nombre_seccion,
                    m.nombre as materia_nombre
                  FROM estudiantes e
                  JOIN cursos c ON e.curso_id = c.id
                  JOIN materias m ON c.materia_id = m.id
                  WHERE e.id = ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$estudianteId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Crear nuevo estudiante
     */
    public function crear($datos) {
        $query = "INSERT INTO estudiantes 
                  (curso_id, cedula, apellido, nombre, email, genero, estado, fecha_inscripcion)
                  VALUES (?, ?, ?, ?, ?, ?, 'activo', NOW())";
        
        $stmt = $this->conexion->prepare($query);
        $result = $stmt->execute([
            $datos['curso_id'],
            $datos['cedula'],
            $datos['apellido'],
            $datos['nombre'],
            $datos['email'],
            $datos['genero'] ?? 'M'
        ]);
        
        return $result ? $this->conexion->lastInsertId() : false;
    }
    
    /**
     * Actualizar estudiante
     */
    public function actualizar($id, $datos) {
        $query = "UPDATE estudiantes 
                  SET apellido = ?, nombre = ?, email = ?, genero = ?
                  WHERE id = ?";
        
        $stmt = $this->conexion->prepare($query);
        return $stmt->execute([
            $datos['apellido'],
            $datos['nombre'],
            $datos['email'],
            $datos['genero'] ?? 'M',
            $id
        ]);
    }
    
    /**
     * Eliminar (retirar) estudiante
     */
    public function retirar($id) {
        $query = "UPDATE estudiantes 
                  SET estado = 'retirado'
                  WHERE id = ?";
        
        $stmt = $this->conexion->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Verificar si cédula ya existe
     */
    public function cedulaExiste($cedula, $excluirId = null) {
        if ($excluirId) {
            $query = "SELECT id FROM estudiantes WHERE cedula = ? AND id != ?";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute([$cedula, $excluirId]);
        } else {
            $query = "SELECT id FROM estudiantes WHERE cedula = ?";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute([$cedula]);
        }
        
        return $stmt->rowCount() > 0;
    }
}