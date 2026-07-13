<?php
// includes/tasa_bcv.php
// Servicio de tasa BCV (Bs. por USD).
// Prioridad: tasa manual de hoy > tasa API de hoy (caché en BD) > última conocida.

function obtenerTasaBCV(PDO $pdo, $forzarApi = false) {
    $hoy = date('Y-m-d');

    $stmt = $pdo->prepare("SELECT fecha, tasa, origen FROM tasas_bcv WHERE fecha = ?");
    $stmt->execute([$hoy]);
    $fila = $stmt->fetch();

    // La tasa manual del día siempre gana; la de API se reutiliza salvo refresco forzado.
    if ($fila && ($fila['origen'] === 'manual' || !$forzarApi)) {
        return ["tasa" => (float)$fila['tasa'], "fecha" => $fila['fecha'], "origen" => $fila['origen']];
    }

    $tasaApi = consultarApiBcv();
    if ($tasaApi > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO tasas_bcv (fecha, tasa, origen) VALUES (?, ?, 'api')
            ON DUPLICATE KEY UPDATE tasa = VALUES(tasa), origen = 'api'
        ");
        $stmt->execute([$hoy, $tasaApi]);
        return ["tasa" => $tasaApi, "fecha" => $hoy, "origen" => "api"];
    }

    // Sin internet/API caída: usar la última tasa conocida.
    $fila = $pdo->query("SELECT fecha, tasa, origen FROM tasas_bcv ORDER BY fecha DESC LIMIT 1")->fetch();
    if ($fila) {
        return ["tasa" => (float)$fila['tasa'], "fecha" => $fila['fecha'], "origen" => $fila['origen']];
    }

    return ["tasa" => 0, "fecha" => $hoy, "origen" => "ninguna"];
}

function consultarApiBcv() {
    $contexto = stream_context_create(["http" => ["timeout" => 4]]);
    $json = @file_get_contents("https://ve.dolarapi.com/v1/dolares/oficial", false, $contexto);
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['promedio']) && (float)$data['promedio'] > 0) {
            return (float)$data['promedio'];
        }
    }
    return 0;
}

function guardarTasaManual(PDO $pdo, $tasa) {
    $stmt = $pdo->prepare("
        INSERT INTO tasas_bcv (fecha, tasa, origen) VALUES (?, ?, 'manual')
        ON DUPLICATE KEY UPDATE tasa = VALUES(tasa), origen = 'manual'
    ");
    $stmt->execute([date('Y-m-d'), $tasa]);
}
