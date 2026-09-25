<?php
require_once __DIR__ . '/config.php';

/**
 * Calls the sickw.com API for a given IMEI/Serial and service ID.
 * Returns ['ok' => bool, 'result' => string|null, 'raw' => array|null, 'error' => string|null]
 */
function sickw_check(string $imei, string $serviceId): array {
    if (SICKW_API_KEY === '') {
        return ['ok' => false, 'result' => null, 'raw' => null, 'error' => 'API key is not configured on the server.'];
    }

    $url = SICKW_API_URL . '?' . http_build_query([
        'format' => 'beta',
        'key' => SICKW_API_KEY,
        'imei' => $imei,
        'service' => $serviceId,
    ]);

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 60,
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        return ['ok' => false, 'result' => null, 'raw' => null, 'error' => 'Connection error: ' . $curlError];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['ok' => false, 'result' => null, 'raw' => null, 'error' => 'Invalid response from provider.'];
    }

    if (($data['status'] ?? '') !== 'success') {
        $errorMsg = is_array($data['result'] ?? null) ? json_encode($data['result']) : (string) ($data['result'] ?? 'Unknown error from provider.');
        return ['ok' => false, 'result' => null, 'raw' => $data, 'error' => $errorMsg];
    }

    // Flatten the "result" object into readable text for storage/display
    $resultText = '';
    if (is_array($data['result'])) {
        foreach ($data['result'] as $key => $value) {
            $resultText .= $key . ': ' . $value . "\n";
        }
    } else {
        $resultText = (string) $data['result'];
    }

    return ['ok' => true, 'result' => trim($resultText), 'raw' => $data, 'error' => null];
}
