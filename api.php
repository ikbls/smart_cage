<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["status" => "error", "message" => $msg]);
    exit;
}

// ================= GET DATA =================
if ($method === 'GET') {
    try {
        try {
            $stmt = $pdo->query(
                "SELECT *, EXTRACT(EPOCH FROM created_at)::bigint AS ts
                 FROM sensor_data
                 ORDER BY created_at DESC
                 LIMIT 20"
            );
        } catch (Exception $e) {
            // cadangan: query sederhana tanpa kolom ts
            $stmt = $pdo->query(
                "SELECT * FROM sensor_data ORDER BY created_at DESC LIMIT 20"
            );
        }
        echo json_encode([
            "status"      => "success",
            "server_time" => time(),
            "data"        => $stmt->fetchAll()
        ]);
    } catch (Exception $e) {
        fail($e->getMessage(), 500);
    }
}

// ================= INSERT DATA =================
// Mendukung 2 format:
//  1) Satu data   : {"temperature":..,"humidity":..,"fan_status":"ON","lamp_status":"OFF"}
//  2) Banyak data : {"readings":[{... , "recorded_at": <epoch detik>}, ...]}
// "recorded_at" (opsional) = waktu pembacaan di perangkat, dipakai saat
// data dikirim terlambat karena perangkat sempat offline.
elseif ($method === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) fail("JSON tidak valid");

    $readings = (isset($input['readings']) && is_array($input['readings']))
        ? $input['readings']
        : [$input];

    if (count($readings) === 0 || count($readings) > 100) {
        fail("Jumlah data harus 1-100");
    }

    $sqlNow = "INSERT INTO sensor_data (temperature, humidity, fan_status, lamp_status)
               VALUES (?, ?, ?, ?)";

    // created_at dari perangkat; lewati jika baris dengan waktu sama sudah ada
    // (mencegah duplikat kalau pengiriman ulang setelah gagal di tengah jalan)
    $sqlTs = "INSERT INTO sensor_data (temperature, humidity, fan_status, lamp_status, created_at)
              SELECT CAST(? AS numeric), CAST(? AS numeric), CAST(? AS text), CAST(? AS text),
                     to_timestamp(CAST(? AS double precision))
              WHERE NOT EXISTS (
                  SELECT 1 FROM sensor_data
                  WHERE created_at = to_timestamp(CAST(? AS double precision))
              )";

    try {
        $pdo->beginTransaction();
        $stNow = $pdo->prepare($sqlNow);
        $stTs  = $pdo->prepare($sqlTs);
        $inserted = 0;
        $skipped  = 0;

        foreach ($readings as $r) {
            if (!isset($r['temperature'], $r['humidity'], $r['fan_status'], $r['lamp_status'])
                || !is_numeric($r['temperature']) || !is_numeric($r['humidity'])) {
                throw new InvalidArgumentException("Data tidak lengkap");
            }
            $t = (float)$r['temperature'];
            $h = (float)$r['humidity'];
            if ($t < -40 || $t > 100 || $h < 0 || $h > 100) {
                throw new InvalidArgumentException("Nilai sensor di luar batas");
            }
            $fan  = strtoupper((string)$r['fan_status'])  === 'ON' ? 'ON' : 'OFF';
            $lamp = strtoupper((string)$r['lamp_status']) === 'ON' ? 'ON' : 'OFF';

            $ts = isset($r['recorded_at']) ? (int)$r['recorded_at'] : 0;
            $validTs = $ts > 1700000000 && $ts <= time() + 86400;

            if ($validTs) {
                $stTs->execute([$t, $h, $fan, $lamp, $ts, $ts]);
                $stTs->rowCount() ? $inserted++ : $skipped++;
            } else {
                $stNow->execute([$t, $h, $fan, $lamp]);
                $inserted++;
            }
        }

        $pdo->commit();
        echo json_encode([
            "status"   => "success",
            "inserted" => $inserted,
            "skipped"  => $skipped
        ]);

    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        fail($e->getMessage());
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        fail($e->getMessage(), 500);
    }
}
?>
