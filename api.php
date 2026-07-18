<?php

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header('Access-Control-Allow-Headers: Content-Type');

require 'db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

// ================= GET DATA =================

if ($method === 'GET') {

    try {

        $stmt = $pdo->query(
            "SELECT * FROM sensor_data
             ORDER BY created_at DESC
             LIMIT 20"
        );

        $data = $stmt->fetchAll();

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);

    } catch (Exception $e) {

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}

// ================= INSERT DATA =================

elseif ($method === 'POST') {

    $json =
    file_get_contents('php://input');

    $data =
    json_decode($json, true);

    if (

        isset($data['temperature']) &&
        isset($data['humidity']) &&
        isset($data['fan_status']) &&
        isset($data['lamp_status'])

    ) {

        try {

            $stmt =
            $pdo->prepare(

                "INSERT INTO sensor_data
                (
                    temperature,
                    humidity,
                    fan_status,
                    lamp_status
                )

                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([

                $data['temperature'],
                $data['humidity'],
                $data['fan_status'],
                $data['lamp_status']

            ]);

            echo json_encode([

                "status" => "success",
                "message" => "Data berhasil disimpan"

            ]);

        } catch (Exception $e) {

            echo json_encode([

                "status" => "error",
                "message" => $e->getMessage()

            ]);
        }

    } else {

        echo json_encode([

            "status" => "error",
            "message" => "Data tidak lengkap"

        ]);
    }
}
?>