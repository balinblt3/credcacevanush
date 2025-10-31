# Imaginea de bază PHP cu serverul Apache inclus
FROM php:8.2-apache

# 1. Instalează pachetele necesare, inclusiv FFmpeg și libmp3lame
RUN apt-get update && \
    apt-get install -y ffmpeg libmp3lame-tools && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# 2. Setează modulul rewrite
RUN a2enmod rewrite

# 3. Permite citirea fișierelor .htaccess în directorul /var/www/html (crucial)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 4. Copiază fișierele de intrare (stream.php și generate.php)
COPY stream.php /var/www/html/
COPY generate.php /var/www/html/
COPY .htaccess /var/www/html/
COPY index.html /var/www/html/

# 5. Setează permisiunile (FOARTE IMPORTANT: Permite Apache să scrie în directorul cache)
RUN mkdir /var/www/html/cache && \
    chown -R www-data:www-data /var/www/html

# Portul 80 este portul implicit
EXPOSE 80
