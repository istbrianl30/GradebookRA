<?php
/**
 * Modelo de Calificaciones
 * GradeBook RA
 */

class Calificacion {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    /**
     * Obtener calificaciones de un estudiante
     */
    public function obtenerCalificacionesEstudiante($estudianteId) {
        $estudianteId = (int)$estudianteId;
        
        $query = "SELECT 
                    cal.id,
                    cal.puntos_obtenidos,
                    cal.nota_final,
                    cal.estado,
                    a.id as actividad_id,
                    a.nombre as actividad_nombre,
                    a.tipo,
                    a.valor_puntos,
                    a.escala_calificacion,
                    ra.codigo as ra_codigo,
                    ra.descripcion as ra_descripcion
                  FROM calificaciones cal
                  JOIN actividades a ON cal.actividad_id = a.id
                  JOIN resultados_aprendizaje ra ON a.ra_id = ra.id
                  WHERE cal.estudiante_id = ?
                  ORDER BY ra.codigo, a.nombre";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$estudianteId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener promedios por RA de un estudiante
     */
    public function obtenerPromediosPorRA($estudianteId) {
        $estudianteId = (int)$estudianteId;
        
        $query = "SELECT 
                    ra.id,
                    ra.codigo,
                    ra.descripcion,
                    ra.porcentaje_ponderacion,
                    pr.promedio_ra,
                    pr.cantidad_actividades,
                    pr.actividades_calificadas
                  FROM resultados_aprendizaje ra
                  LEFT JOIN promedios_ra pr ON ra.id = pr.ra_id AND pr.estudiante_id = ?
                  WHERE ra.estado = 'activo'
                  ORDER BY ra.codigo";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$estudianteId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Guardar calificación
     */
    public function guardar($datos) {
        $estudianteId = (int)$datos['estudiante_id'];
        $actividadId = (int)$datos['actividad_id'];
        $puntosObtenidos = (float)$datos['puntos_obtenidos'];
        $observaciones = $datos['observaciones'] ?? '';
        
        // Obtener datos de la actividad
        $queryActividad = "SELECT valor_puntos, escala_calificacion FROM actividades WHERE id = ?";
        $stmtActividad = $this->conexion->prepare($queryActividad);
        $stmtActividad->execute([$actividadId]);
        $actividad = $stmtActividad->fetch(PDO::FETCH_ASSOC);
        
        if (!$actividad) {
            return false;
        }
        
        // Calcular porcentaje y nota
        $porcentaje = ($puntosObtenidos / $actividad['valor_puntos']) * 100;
        $notaFinal = ($porcentaje / 100) * $actividad['escala_calificacion'];
        $notaFinal = round($notaFinal, 2);
        
        // Insertar o actualizar calificación
        $query = "INSERT INTO calificaciones 
                  (estudiante_id, actividad_id, puntos_obtenidos, porcentaje_calificacion, nota_final, observaciones_docente, estado, fecha_calificacion)
                  VALUES (?, ?, ?, ?, ?, ?, 'calificada', NOW())
                  ON DUPLICATE KEY UPDATE
                  puntos_obtenidos = ?,
                  porcentaje_calificacion = ?,
                  nota_final = ?,
                  observaciones_docente = ?,
                  estado = 'calificada',
                  fecha_calificacion = NOW()";
        
        try {
            $stmt = $this->conexion->prepare($query);
            $result = $stmt->execute([
                $estudianteId, $actividadId, $puntosObtenidos, $porcentaje, $notaFinal, $observaciones,
                $puntosObtenidos, $porcentaje, $notaFinal, $observaciones
            ]);
            
            if ($result) {
                // Recalcular promedios
                $this->recalcularPromedios($estudianteId, $actividadId);
                return true;
            }
            
            // Debug: log why it failed
            error_log("Execute result: " . ($result ? "true" : "false") . " - rowCount: " . $stmt->rowCount());
        } catch (Exception $e) {
            error_log("Error guardando calificación: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: " . json_encode([
                $estudianteId, $actividadId, $puntosObtenidos, $porcentaje, $notaFinal, $observaciones
            ]));
        }
        return false;
    }
    
    /**
     * Recalcular promedios de RA
     */
    public function recalcularPromedios($estudianteId, $actividadId) {
        $estudianteId = (int)$estudianteId;
        $actividadId = (int)$actividadId;
        
        // Obtener RA de la actividad
        $query = "SELECT ra.id FROM actividades a 
                  JOIN resultados_aprendizaje ra ON a.ra_id = ra.id 
                  WHERE a.id = ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$actividadId]);
        
        if ($stmt->rowCount() > 0) {
            $ra = $stmt->fetch(PDO::FETCH_ASSOC);
            $raId = $ra['id'];
            
            // Calcular promedio del RA
            $queryPromedioRA = "SELECT 
                                ROUND(AVG(COALESCE(cal.nota_final, 0)), 2) as promedio,
                                COUNT(DISTINCT a.id) as total_actividades,
                                COUNT(DISTINCT CASE WHEN cal.estado IN ('calificada', 'revisada') THEN cal.id END) as calificadas
                                FROM actividades a
                                LEFT JOIN calificaciones cal ON a.id = cal.actividad_id AND cal.estudiante_id = ?
                                WHERE a.ra_id = ?";
            
            $stmtPromedio = $this->conexion->prepare($queryPromedioRA);
            $stmtPromedio->execute([$estudianteId, $raId]);
            $promedioData = $stmtPromedio->fetch(PDO::FETCH_ASSOC);
            
            // Handle NULL values
            $promedio = $promedioData['promedio'] ?? 0;
            $totalActividades = $promedioData['total_actividades'] ?? 0;
            $calificadas = $promedioData['calificadas'] ?? 0;
            
            // Insertar o actualizar en promedios_ra
            $queryActualizar = "INSERT INTO promedios_ra 
                                (estudiante_id, ra_id, promedio_ra, cantidad_actividades, actividades_calificadas)
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                promedio_ra = ?,
                                actividades_calificadas = ?";
            
            $stmtActualizar = $this->conexion->prepare($queryActualizar);
            $stmtActualizar->execute([
                $estudianteId, $raId, $promedio, $totalActividades, $calificadas,
                $promedio, $calificadas
            ]);
            
            // Recalcular nota final del estudiante
            $this->recalcularNotaFinalEstudiante($estudianteId);
        }
    }
    
    /**
     * Recalcular nota final ponderada del estudiante
     */
    public function recalcularNotaFinalEstudiante($estudianteId) {
        $estudianteId = (int)$estudianteId;
        
        // Obtener curso del estudiante
        $queryCurso = "SELECT curso_id FROM estudiantes WHERE id = ?";
        $stmtCurso = $this->conexion->prepare($queryCurso);
        $stmtCurso->execute([$estudianteId]);
        $estudiante = $stmtCurso->fetch(PDO::FETCH_ASSOC);
        
        if (!$estudiante) {
            return; // Student not found
        }
        
        $cursoId = $estudiante['curso_id'];
        
        // Calcular nota final ponderada
        $queryNotaFinal = "SELECT 
                           ROUND(SUM(COALESCE(pr.promedio_ra, 0) * ra.porcentaje_ponderacion / 100), 2) as nota_final
                           FROM resultados_aprendizaje ra
                           LEFT JOIN promedios_ra pr ON ra.id = pr.ra_id AND pr.estudiante_id = ?
                           WHERE ra.estado = 'activo' AND ra.curso_id = ?";
        
        $stmtNotaFinal = $this->conexion->prepare($queryNotaFinal);
        $stmtNotaFinal->execute([$estudianteId, $cursoId]);
        $notaData = $stmtNotaFinal->fetch(PDO::FETCH_ASSOC);
        
        $notaFinal = $notaData['nota_final'] ?? 0;
        
        // Actualizar nota final en tabla estudiantes
        $queryActualizar = "UPDATE estudiantes SET nota_final = ? WHERE id = ?";
        $stmtActualizar = $this->conexion->prepare($queryActualizar);
        $stmtActualizar->execute([$notaFinal, $estudianteId]);
    }
    
    /**
     * Obtener actividades de un RA para calificar
     */
    public function obtenerActividadesParaCalificar($raId) {
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

?>