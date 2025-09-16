<?php
header("Content-Type: application/json");
http_response_code(200); // pastikan selalu JSON valid

$input = json_decode(file_get_contents("php://input"), true);
$prompt = $input["prompt"] ?? "";

// Simpan API key di server
$apiKey = "sk-or-v1-xxxx"; // ganti dengan API key OpenRouter kamu

$ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $apiKey,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => "openai/gpt-3.5-turbo", // pastikan nama model valid
    "messages" => [
        ["role" => "user", "content" => $prompt]
    ]
]));

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        "error" => true,
        "message" => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// Kalau API error → kirim pesan error
if ($httpCode !== 200 || isset($data["error"])) {
    echo json_encode([
        "error" => true,
        "status" => $httpCode,
        "message" => $data["error"]["message"] ?? "Unknown error"
    ]);
    exit;
}

// Kalau sukses → kirim JSON apa adanya
echo json_encode($data);
