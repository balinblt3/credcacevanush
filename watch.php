<?php
// watch.php - Extrage URL-ul de streaming și încearcă transcodarea FFmpeg la MP3

// Setează antetul pentru un flux audio MP3
header('Content-Type: audio/mpeg'); 
// Setează o limită de timp mare pentru ca stream-ul să nu fie întrerupt
set_time_limit(0); 

// Dezactivează toate erorile și output-urile de debug
error_reporting(0);
ini_set('display_errors', 0);

$videoid = $_GET['videoid'] ?? '';

// Validare ID YouTube
if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoid)) {
    http_response_code(400);
    exit('Invalid YouTube ID');
}

// Pasul 1: Extragerea URL-ului de la ytdlp.online
$api_url = "https://ytdlp.online/stream?command=" . urlencode("--get-url https://www.youtube.com/watch?v=" . $videoid);

// Deschide stream-ul pentru a extrage URL-ul real
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
    ]
]);

$stream = @fopen($api_url, 'r', false, $context);
if (!$stream) {
    http_response_code(500);
    exit('Failed to connect to API');
}

// Variabile de stocare a URL-urilor audio
$audio_count = 0;
$first_audio_url = null;
$second_audio_url = null; 

// Citirea și procesarea stream-ului
while (!feof($stream)) {
    $line = fgets($stream);
    if ($line === false) break;

    // Caută linia care începe cu "data: https://"
    if (strpos($line, 'data: https://') === 0) {
        $url = trim(substr($line, 6)); // elimină "data: "

        // Verificăm dacă e URL audio (itag-urile tipice pentru audio)
        // Nu excludem M3U8/DASH, deoarece FFmpeg va transcodifica
        if (strpos($url, 'mime=audio') !== false || 
            strpos($url, 'itag=140') !== false || 
            strpos($url, 'itag=251') !== false ||
            strpos($url, 'itag=249') !== false) {
            
            $audio_count++;
            
            // Salvează primul URL ca fallback
            if ($audio_count === 1) {
                $first_audio_url = $url;
            }
            
            // Selectează AL DOILEA LINK și se oprește
            if ($audio_count === 2) {
                $second_audio_url = $url;
                break;
            }
        }
    }
}

fclose($stream);

// Determinăm URL-ul final care va fi folosit de FFmpeg
$final_audio_url = $second_audio_url ?: $first_audio_url;

if (!$final_audio_url) {
    http_response_code(404);
    exit('No audio URL found');
}


// Pasul 2: Apelarea FFmpeg pentru transcodare la MP3 și streaming

// Comanda FFmpeg:
// -i <url> = Sursa de intrare
// -vn = Fără video
// -acodec libmp3lame = Codec MP3 standard
// -b:a 192k = Bitrate audio (poți ajusta)
// -f mp3 = Forțează formatul de output al stream-ului la MP3
// pipe:1 = Trimite output-ul la stdout
$ffmpeg_command = "ffmpeg -i " . escapeshellarg($final_audio_url) . " -vn -acodec libmp3lame -b:a 192k -f mp3 pipe:1";

// Descriptori pentru proces
$descriptorspec = array(
    0 => array("pipe", "r"), // stdin (nefolosit)
    1 => array("pipe", "w"), // stdout (fluxul audio)
    2 => array("file", "/tmp/ffmpeg_errors.log", "a") // stderr (pentru debug)
);

// Deschide procesul FFmpeg
$process = proc_open($ffmpeg_command, $descriptorspec, $pipes);

if (is_resource($process)) {
    // Închide pipe-ul de intrare
    fclose($pipes[0]);

    // Buclează și trimite datele primite de la FFmpeg către client
    while (!feof($pipes[1])) {
        // Citim în bucăți și trimitem imediat
        echo fread($pipes[1], 8192); 
        flush(); // Forțează trimiterea buffer-ului către client (crucial pentru streaming)
    }

    // Curățare
    fclose($pipes[1]);
    proc_close($process);
    
    // Ieșire curată
    exit();
} else {
    // Eroare la pornirea procesului FFmpeg
    http_response_code(500);
    exit('Failed to execute FFmpeg process.');
}
