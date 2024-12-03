<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'juegos');
if ($conn->connect_error) {
    die(json_encode(['mensaje' => 'Error al conectar con la base de datos']));
}

$data = json_decode(file_get_contents('php://input'), true);
$tipo = $data['tipo'] ?? '';

if ($tipo === 'inicio') {
    $nombre = $data['nombre'];
    if ($nombre) {
        $stmt = $conn->prepare("INSERT INTO jugadores (nombre) VALUES (?)");
        $stmt->bind_param('s', $nombre);
        $stmt->execute();
        $_SESSION['jugador_id'] = $conn->insert_id;

        $resultado = $conn->query("SELECT palabra FROM palabras");
        $palabras_db = $resultado->fetch_all(MYSQLI_ASSOC);
        $_SESSION['palabra'] = $palabras_db[array_rand($palabras_db)]['palabra'];
        $_SESSION['progreso'] = str_repeat('_', strlen($_SESSION['palabra']));
        $_SESSION['intentos_restantes'] = 6;

        echo json_encode([
            'progreso' => $_SESSION['progreso'],
            'intentos_restantes' => $_SESSION['intentos_restantes'],
        ]);
    }
} elseif ($tipo === 'letra') {
    $letra = strtolower($data['letra']);
    $palabra = $_SESSION['palabra'];
    $progreso = $_SESSION['progreso'];
    $intentos_restantes = $_SESSION['intentos_restantes'];

    if (strpos($palabra, $letra) !== false) {
        for ($i = 0; $i < strlen($palabra); $i++) {
            if ($palabra[$i] === $letra) {
                $progreso[$i] = $letra;
            }
        }
        $_SESSION['progreso'] = $progreso;
        $mensaje = "¡Bien hecho!";
    } else {
        $intentos_restantes--;
        $_SESSION['intentos_restantes'] = $intentos_restantes;
        $mensaje = "¡Incorrecto!";
    }

    $finalizado = $progreso === $palabra || $intentos_restantes <= 0;
    echo json_encode([
        'mensaje' => $mensaje,
        'progreso' => $_SESSION['progreso'],
        'intentos_restantes' => $intentos_restantes,
        'finalizado' => $finalizado,
    ]);
} elseif ($tipo === 'nueva_palabra') {
    $palabra = strtolower(trim($data['palabra']));
    $stmt = $conn->prepare("INSERT INTO palabras (palabra) VALUES (?)");
    $stmt->bind_param('s', $palabra);
    $stmt->execute();
    echo json_encode(['mensaje' => "Palabra añadida exitosamente"]);
}
?>