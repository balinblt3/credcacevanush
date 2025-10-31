<?php
// stream.php - Serveste MP3-ul din directorul de cache ca un stream continuu

header('Content-Type: audio/mpeg'); 
set_time_limit(0); 

error_reporting(0);
ini_set('display_errors', 0);

$videoid = $_GET['videoid'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoid)) {
    http_response_code(400);
    exit('Invalid YouTube ID');
}

// Directorul unde sunt salvate fișierele MP3
$cache_dir = __DIR__ . '/cache/'; 
$mp3_file = $cache_dir . $videoid . '.mp3';

// 1. Verifică dacă fișierul MP3 există
if (!file_exists($mp3_file)) {
    http_response_code(404);
    // Mesaj special pentru serverul Node.js să știe să apeleze generate.php
    exit('File not generated. Please request generation first.'); 
}

// 2. Servirea fișierului către client (ca un stream)
if ($file_handle = fopen($mp3_file, 'rb')) {
    header('Content-Length: ' . filesize($mp3_file));
    
    // Citirea și trimiterea fișierului în bucăți
    while (!feof($file_handle)) {
        echo fread($file_handle, 8192);
        flush();
    }
    fclose($file_handle);
    exit();
} else {
    http_response_code(500);
    exit('Could not open file for streaming.');
}
