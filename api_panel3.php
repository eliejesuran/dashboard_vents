<?php
// CONFIGURATION : Remplacez ces valeurs par les vôtres.
$master_id = 'YOUR_MASTER_ID';   // À remplacer par le numéro de master_id
$api_key   = 'YOUR_API_KEY';     // À remplacer par votre clé API

// Construire l'URL de l'API avec le master_id
$apiUrl = "https://api.crodeon.com/api/v2/reporters/{$master_id}/measurements/latest";

// Initialiser cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    "X-API-KEY: $api_key"
));

$result = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => curl_error($ch)]);
    exit;
}

curl_close($ch);

// Décoder la réponse JSON
$data = json_decode($result, true);
if (!$data) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse JSON invalide']);
    exit;
}

// Initialiser nos variables pour les vitesses
$avg_speed = null;
$max_speed = null;

// On parcourt les items pour extraire les mesures selon leur channel_index.
// (Adaptez ces valeurs si vos mesures utilisent d'autres indices.)
if (isset($data['items']) && is_array($data['items'])) {
    foreach ($data['items'] as $measurement) {
        if (isset($measurement['channel_index'], $measurement['value'])) {
            if ($measurement['channel_index'] == 0) {
                $avg_speed = $measurement['value'];
            } elseif ($measurement['channel_index'] == 1) {
                $max_speed = $measurement['value'];
            }
        }
    }
}

// Retourner les données en JSON
header('Content-Type: application/json');
echo json_encode([
    'avg_speed' => $avg_speed,
    'max_speed' => $max_speed
]);
?>
