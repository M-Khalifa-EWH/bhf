<?php

header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["question"]) || !isset($input["data"])) {
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$question = $input["question"];
$data = json_encode($input["data"], JSON_UNESCAPED_UNICODE);

// اختر اللغة: en أو de
$language = $input["lang"] ?? "en";

$languageInstruction = $language === "de"
    ? "Answer in clear German."
    : "Answer in clear English.";

$prompt = "
You are a professional data analyst AI.

You analyze time-series and KPI dashboard data.

Rules:
- Be concise
- Focus on insights, trends, anomalies
- Do NOT repeat raw JSON unless needed
- $languageInstruction

DATA:
$data

QUESTION:
$question

Return a helpful analytical answer.
";

$ch = curl_init("https://api.openai.com/v1/chat/completions");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer YOUR_API_KEY"
]);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => "gpt-4.1-mini",
    "messages" => [
        ["role" => "user", "content" => $prompt]
    ],
    "temperature" => 0.3
]));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

echo json_encode([
    "answer" => $result["choices"][0]["message"]["content"] ?? "No response"
]);