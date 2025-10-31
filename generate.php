<?php
// generate.php - Descarcă, transcodifică și salvează melodia în cache.

set_time_limit(0); 
error_reporting(0);
ini_set('display_errors', 0);

$videoid = $_GET['videoid'] ?? '';

$cache_dir = __DIR__ . '/cache/'; 
$mp3_file = $cache_dir . $videoid . '.mp3';
$log_file = $cache_dir . $videoid . '.log'; 

if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0777, true);
}

// 1. Verifică dacă fișierul este deja salvat
if (file_exists($mp3_file)) {
    http_response_code(200);
    exit("Cache already exists for $videoid.");
}

// Pasul 2: Extragerea URL-ului de la ytdlp.online
$api_url = "https://ytdlp.online/stream?command=" . urlencode("--get-url https://www.youtube.com/watch?v=" . $videoid);

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

$audio_count = 0;
$first_audio_url = null;
$second_audio_url = null; 

while (!feof($stream)) {
    $line = fgets($stream);
    if ($line === false) break;

    if (strpos($line, 'data: https://') === 0) {
        $url = trim(substr($line, 6)); 

        if (strpos($url, 'mime=audio') !== false || 
            strpos($url, 'itag=140') !== false || 
            strpos($url, 'itag=251') !== false ||
            strpos($url, 'itag=249') !== false) {
            
            $audio_count++;
            
            if ($audio_count === 1) {
                $first_audio_url = $url;
            }
            
            if ($audio_count === 2) {
                $second_audio_url = $url;
                break;
            }
        }
    }
}

fclose($stream);

$final_audio_url = $second_audio_url ?: $first_audio_url;

if (!$final_audio_url) {
    http_response_code(404);
    exit('No audio URL found');
}

// Pasul 3: Comanda FFmpeg pentru SALVARE pe disc
// Folosește comanda 'exec' pentru a rula în background
$ffmpeg_command = "ffmpeg -i " . escapeshellarg($final_audio_url) . 
                  " -vn -acodec libmp3lame -b:a 192k -f mp3 " . 
                  escapeshellarg($mp3_file) . " > " . escapeshellarg($log_file) . " 2>&1 &"; 

exec($ffmpeg_command);

http_response_code(200);
exit("Started generating cache for $videoid.");
