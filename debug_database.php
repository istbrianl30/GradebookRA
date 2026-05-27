<?php
/**
 * Database diagnostic script for GradeBook RA
 * Run this after ensuring MySQL is running to debug connection and data issues
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    echo "Attempting to connect to database...<br>";
    // Test connection
    $stmt = $conn->query("SELECT 1");
    $stmt->fetch();
    echo "<span style='color: green;'>✓ Database connection successful!</span><br><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</span><br><br>";
    echo "<h3>Troubleshooting steps:</h3>";
    echo "<ol>";
    echo "<li>Ensure MySQL service is started in XAMPP Control Panel</li>";
    echo "<li>Verify database credentials in config/database.php</li>";
    echo "<li>Check if MySQL is running on standard port 3306</li>";
    echo "<li>Try connecting with: mysql -u root -p (if using command line)</li>";
    echo "</ol>";
    return;
}

echo "<h2>Database Information</h2>";
$dbName = $conn->query("SELECT DATABASE()")->fetchColumn();
echo "Current database: <strong>" . htmlspecialchars($dbName) . "</strong><br><br>";

echo "<h2>Tables in Database</h2>";
$result = $conn->query("SHOW TABLES");
$tables = $result->fetch_all(MYSQLI_NUM);
if (empty($tables)) {
    echo "<span style='color: orange;'>No tables found. Database may be empty or not initialized.</span><br>";
} else {
    echo "<table border='1' cellpadding='5'><tr><th>Table Name</th><th>Row Count</th></tr>";
    foreach ($tables as $table) {
        $tableName = $table[0];
        $count = $conn->query("SELECT COUNT(*) FROM `$tableName`")->fetchColumn();
        echo "<tr><td>" . htmlspecialchars($tableName) . "</td><td>" . number_format($count) . "</td></tr>";
    }
    echo "</table><br>";
}

echo "<h2>Checking GradeBook Schema</h2>";
$requiredTables = ['usuarios', 'materias', 'cursos', 'estudiantes', 'resultados_aprendizaje', 'actividades', 'calificaciones'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
    if (!$exists) {
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "<span style='color: red;'>Missing tables: " . implode(', ', $missingTables) . "</span><br>";
    echo "You may need to run the database initialization script.<br><br>";
} else {
    echo "<span style='color: green;'>All required tables found.</span><br><br>";
}

// Check data in key tables if they exist
if (!in_array('usuarios', $missingTables)) {
    echo "<h2>Usuarios Table (Users)</h2>";
    $result = $conn->query("SELECT id, nombre, apellido, email, cargo FROM usuarios");
    $usuarios = $result->fetch_all(MYSQLI_ASSOC);
    if (empty($usuarios)) {
        echo "<span style='color: orange;'>No users found.</span><br>";
    } else {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Cargo</th></tr>";
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . $usuario['id'] . "</td>";
            echo "<td>" . htmlspecialchars($usuario['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['apellido']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['cargo']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
}

if (!in_array('materias', $missingTables)) {
    echo "<h2>Materias Table (Subjects)</h2>";
    $result = $conn->query("SELECT m.id, m.nombre, m.codigo, u.nombre as usuario_nombre FROM materias m JOIN usuarios u ON m.usuario_id = u.id");
    $materias = $result->fetch_all(MYSQLI_ASSOC);
    if (empty($materias)) {
        echo "<span style='color: orange;'>No subjects found.</span><br>";
    } else {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Nombre</th><th>Código</th><th>Usuario</th></tr>";
        foreach ($materias as $materia) {
            echo "<tr>";
            echo "<td>" . $materia['id'] . "</td>";
            echo "<td>" . htmlspecialchars($materia['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($materia['codigo']) . "</td>";
            echo "<td>" . htmlspecialchars($materia['usuario_nombre']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
}

if (!in_array('cursos', $missingTables)) {
    echo "<h2>Cursos Table (Courses)</h2>";
    $result = $conn->query("SELECT c.id, m.nombre as materia_nombre, c.nombre_seccion, c.anio_academico, c.semestre, c.estado FROM cursos c JOIN materias m ON c.materia_id = m.id");
    $cursos = $result->fetch_all(MYSQLI_ASSOC);
    if (empty($cursos)) {
        echo "<span style='color: orange;'>No courses found.</span><br>";
    } else {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Materia</th><th>Sección</th><th>Año</th><th>Semestre</th><th>Estado</th></tr>";
        foreach ($cursos as $curso) {
            echo "<tr>";
            echo "<td>" . $curso['id'] . "</td>";
            echo "<td>" . htmlspecialchars($curso['materia_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($curso['nombre_seccion']) . "</td>";
            echo "<td>" . $curso['anio_academico'] . "</td>";
            echo "<td>" . htmlspecialchars($curso['semestre']) . "</td>";
            echo "<td>" . htmlspecialchars($curso['estado']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
}

// Test the actual dashboard queries
echo "<h2>Dashboard Query Test</h2>";
// Get the first user (assuming admin)
$usuarioResult = $conn->query("SELECT id FROM usuarios LIMIT 1");
if ($usuarioResult->num_rows > 0) {
    $usuarioId = $usuarioResult->fetchColumn();
    echo "Testing with user ID: " . $usuarioId . "<br>";
    
    // Test obtenerCursosPorUsuario equivalent
    echo "<h3>Cursos por usuario:</h3>";
    $query = "SELECT c.*, m.nombre as materia_nombre, m.codigo as materia_codigo 
              FROM cursos c
              JOIN materias m ON c.materia_id = m.id
              WHERE m.usuario_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cursos = $result->fetch_all(MYSQLI_ASSOC);
    
    if (empty($cursos)) {
        echo "<span style='color: orange;'>No courses found for this user.</span><br>";
    } else {
        echo "Found " . count($cursos) . " courses:<br>";
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Materia</th><th>Sección</th><th>Año</th><th>Semestre</th><th>Acciones</th></tr>";
        foreach ($cursos as $curso) {
            echo "<tr>";
            echo "<td>" . $curso['id'] . "</td>";
            echo "<td>" . htmlspecialchars($curso['materia_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($curso['nombre_seccion']) . "</td>";
            echo "<td>" . $curso['anio_academico'] . "</td>";
            echo "<td>" . htmlspecialchars($curso['semestre']) . "</td>";
            echo "<td>Ver</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // Test obtenerEstadisticasGenerales equivalent
    echo "<h3>Estadísticas generales:</h3>";
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
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    echo "<table border='1' cellpadding='5'><tr><th>Métrica</th><th>Valor</th></tr>";
    echo "<tr><td>Total Materias</td><td>" . $stats['total_materias'] . "</td></tr>";
    echo "<tr><td>Total Cursos</td><td>" . $stats['total_cursos'] . "</td></tr>";
    echo "<tr><td>Total Estudiantes</td><td>" . $stats['total_estudiantes'] . "</td></tr>";
    echo "<tr><td>Promedio General</td><td>" . $stats['promedio_general'] . "</td></tr>";
    echo "</table><br>";
    
    $stmt->close();
} else {
    echo "<span style='color: red;'>No users found in database. Cannot test user-specific queries.</span><br>";
}

echo "<h2>Recommendations</h2>";
echo "<ol>";
if ($conn->connect_error) {
    echo "<li><strong>Fix database connection:</strong> Start MySQL service in XAMPP Control Panel</li>";
}
if (empty($tables)) {
    echo "<li><strong>Initialize database:</strong> Run your database setup/migration scripts</li>";
}
if (empty($usuarios)) {
    echo "<li><strong>Create admin user:</strong> You need to create at least one user in the usuarios table</li>";
}
if (empty($materias)) {
    echo "<li><strong>Add subjects:</strong> Add materias (subjects) for the users</li>";
}
if (empty($cursos)) {
    echo "<li><strong>Add courses:</strong> Add cursos (courses) linked to materias</li>";
}
echo "<li><strong>Check table structure:</strong> Verify that your tables have the expected columns</li>";
echo "<li><strong>Check data relationships:</strong> Ensure foreign keys are properly set</li>";
echo "</ol>";

$conn->close();
?>