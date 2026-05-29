<?php
// ========================= db_config.php =========================
// Konfigurasi koneksi PostgreSQL Supabase via PDO
// =================================================================
 
$host     = "aws-1-ap-southeast-1.pooler.supabase.com";
$port     = "6543";
$dbname   = "postgres";
$user     = "postgres.rutxjgtbzaodbektmobq";
$password = "Carjang1205";
 
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
 
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 10,    // timeout koneksi 10 detik
    ]);
 
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        "status"  => "error",
        "message" => "Koneksi database gagal: " . $e->getMessage()
    ]));
}
?>