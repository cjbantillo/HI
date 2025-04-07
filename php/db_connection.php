<?php
// Supabase credentials
define('SUPABASE_URL', 'https://okyzaaalihdgefcavioe.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9reXphYWFsaWhkZ2VmY2F2aW9lIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDM5MTU4OTMsImV4cCI6MjA1OTQ5MTg5M30.fkBsZZ0J5HmIJ6a0YEBJOxLQijvichQ7p0J2xlhObF0');

// Function to make HTTP requests to Supabase
function supabase_request($method, $endpoint, $data = null) {
    $url = SUPABASE_URL . $endpoint;
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ];

    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $data ? json_encode($data) : null
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        throw new Exception("Error connecting to Supabase: " . ($error['message'] ?? 'Unknown error') . " | URL: $url | Method: $method | Data: " . json_encode($data));
    }

    // Decode JSON response for GET requests or return raw response for others
    if ($method === 'GET') {
        return json_decode($response, true); // Returns array or null if invalid JSON
    }

    // For POST, PATCH, DELETE, return true on success (no need for response data)
    return true;
}
?>