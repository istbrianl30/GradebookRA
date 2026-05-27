<?php
$configs = [
    ['host' => 'localhost', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => 'localhost', 'port' => 3307],
    ['host' => '127.0.0.1', 'port' => 3307],
];

$user = 'root';
$pass = '';

foreach ($configs as $config) {
    $host = $config['host'];
    $port = $config['port'];
    
    echo "Trying $host:$port...<br>";
    $conn = new mysqli($host, $user, $pass, '', $port);
    if ($conn->connect_error) {
        echo "Failed: " . $conn->connect_error . "<br><br>";
    } else {
        echo "Success! Connected to MySQL at $host:$port<br>";
        echo "MySQL version: " . $conn->server_info . "<br>";
        
        // List databases
        $result = $conn->query("SHOW DATABASES");
        if ($result) {
            echo "Databases:<br>";
            while ($row = $result->fetch_assoc()) {
                echo "- " . $row['Database'] . "<br>";
            }
            $result->free();
        }
        
        $conn->close();
        echo "<br>";
        break; // Stop at first successful connection
    }
}
?>