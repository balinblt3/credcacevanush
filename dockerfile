# Imaginea de bază PHP cu serverul Apache inclus
FROM php:8.2-apache

# 1. Instalează pachetele necesare, inclusiv FFmpeg
RUN apt-get update && \
    apt-get install -y ffmpeg && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# 2. Setează modulul rewrite (l-ai avut probabil deja)
RUN a2enmod rewrite

# NOU: Permite citirea fișierelor .htaccess în directorul /var/www/html
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 3. Copiază fișierele de intrare 
COPY watch.php /var/www/html/
COPY .htaccess /var/www/html/ 
COPY index.html /var/www/html/ 
# Asigură-te că index.html (sau index.php) este prezent pentru health check

# 4. Setează permisiunile
RUN chown -R www-data:www-data /var/www/html

# Portul 80 este portul implicit
EXPOSE 80
