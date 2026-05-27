<?php
/**
 * Modelo de Resultados de Aprendizaje (RA)
 * GradeBook RA
 */

class RA {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    /**
     * Obtener detalle de un RA por su ID
     */
    public function obtenerRADetalle($raId) {
        $raId = (int)$raId;
        $query = "SELECT ra.*, m.nombre as materia_nombre, m.codigo as materia_codigo 
                  FROM resultados_aprendizaje ra
                  JOIN materias m ON ra.materia_id = m.id
                  WHERE ra.id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$raId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener actividades de un RA
     */
    public function obtenerActividadesRA($raId) {
        $raId = (int)$raId;
        $query = "SELECT 
                    a.id,
                    a.nombre,
                    a.tipo,
                    a.valor_puntos,
                    a.escala_calificacion,
                    a.porcentaje_ponderacion
                  FROM actividades a
                  WHERE a.ra_id = ? AND a.estado = 'activa'
                  ORDER BY a.nombre";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$raId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}