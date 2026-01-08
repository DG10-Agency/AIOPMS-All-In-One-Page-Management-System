<?php
// Load WordPress to use HTTP API
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

echo "<h2>AI Connectivity Test (v4 - Model Discovery)</h2>";

$gemini_key = get_option('aiopms_gemini_api_key');

if (empty($gemini_key)) {
    die("❌ Gemini API Key is missing.");
}

$masked = substr($gemini_key, 0, 5) . '...' . substr($gemini_key, -5);
echo "ℹ️ Using Key: $masked<br>";

// 1. Try to LIST available models
echo "<h3>Attempting to List Available Models...</h3>";
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $gemini_key;

$response = wp_remote_get($url, ['timeout' => 15]);

if (is_wp_error($response)) {
    echo "❌ List Request Failed: " . $response->get_error_message() . "<br>";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code === 200 && isset($data['models'])) {
        echo "✅ <strong>Success! Found " . count($data['models']) . " models:</strong><br><br>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Model Name (ID)</th><th>Display Name</th><th>Supported Methods</th></tr>";
        
        foreach ($data['models'] as $model) {
            $name = $model['name']; // e.g., models/gemini-pro
            // Check if it supports generateContent
            $methods = isset($model['supportedGenerationMethods']) ? implode(', ', $model['supportedGenerationMethods']) : 'None';
            
            $style = (strpos($methods, 'generateContent') !== false) ? "background-color: #e6fffa;" : "";
            
            echo "<tr style='$style'>";
            echo "<td><strong>$name</strong></td>";
            echo "<td>" . (isset($model['displayName']) ? $model['displayName'] : '') . "</td>";
            echo "<td>$methods</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br>ℹ️ <em>Models successfully highlighted in green support content generation.</em>";
    } else {
        echo "❌ <strong>Listing Failed (Status: $code)</strong><br>";
        echo "Response: <pre>" . htmlspecialchars($body) . "</pre>";
    }
}
