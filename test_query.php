<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'C:\xampp\htdocs\Pa martin\gradebook_web\config/database.php';

echo "Connection successful!<br>";

$usuarioId = 1; // Assuming admin user has ID 1

$query = "SELECT 
                COUNT(DISTINCT m.id) as total_materias,
                COUNT(DISTINCT c.id) as total_cursos,
                COUNT(DISTINCT e.id) as total_estudiantes,
                ROUND(AVG(e.nota_final), 2) as promedio_general
              FROM materias m
              LEFT JOIN cursos c ON m.id = c.materia_id
              LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.estado = 'activo'
              WHERE m.usuario_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$usuarioId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($result);
echo "</pre>";

// Also test the cursos query
echo "<h2>Cursos query:</h2>";
$query2 = "SELECT c.*, m.nombre as materia_nombre, m.codigo as materia_codigo 
           FROM cursos c
           JOIN materias m ON c.materia_id = m.id
           WHERE m.usuario_id = ?";
$stmt2 = $conn->prepare($query2);
$stmt2->execute([$usuarioId]);
$cursos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($cursos);
echo "</pre>";
echo "Count: " . count($cursos);
?>