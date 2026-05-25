<?php
/**
 * Modelo de Usuarios
 * GradeBook RA
 */

class Usuario {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    /**
     * Autenticar usuario
     */
    public function autenticar($email, $password) {
        // Try both hash methods (PHP sha256 and MySQL SHA2)
        $passwordPhp = hash('sha256', $password);
        $passwordSha256 = strtoupper(sha1($password)); // Fallback
        
        $query = "SELECT id, nombre, apellido, email, cargo, estado 
                  FROM usuarios 
                  WHERE email = ? AND password_hash = ? AND estado = 'activo'";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$email, $passwordPhp]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Try MySQL SHA2 format (uppercase hex)
        $stmt->execute([$email, strtoupper($passwordPhp)]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }
    
    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id) {
        $id = (int)$id;
        $query = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
    
    /**
     * Verificar si email ya existe
     */
    public function emailExiste($email) {
        $query = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }
}

?>