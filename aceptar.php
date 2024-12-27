<?php


// Verifica si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura los datos enviados en formato JSON
    $data = json_decode(file_get_contents('php://input'), true);
    //var_dump($data);
    if (isset($data['id'])) {
        $id = htmlspecialchars($data['id']);
        require_once('./conn.php');
        $stmt = $conn->prepare("UPDATE solicituds SET estado = 1 where id = $id");
        $stmt->execute();
        echo json_encode([
            'success' => true,
            'message' => "aceptado"
        ]);
    }else {
        echo json_encode([
            'success' => false,
            'message' => $data
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
}
?>
