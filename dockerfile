# Imaginea de bază PHP cu Apache
FROM php:8.2-apache

# 1. Instalează pachetele necesare, inclusiv FFmpeg
RUN apt-get update && \
    apt-get install -y ffmpeg && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# 2. Setează modulul rewrite (necesar pentru unele configurări web)
RUN a2enmod rewrite

# 3. Copiază fișierul de intrare (scriptul PHP)
COPY watch.php /var/www/html/

# 4. Setează permisiunile (important)
RUN chown -R www-data:www-data /var/www/html

# Portul 80 este portul implicit pentru Apache
EXPOSE 80
