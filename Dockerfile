# Usa uma imagem Alpine bem leve com PHP 8.3 FPM
FROM php:8.3-fpm-alpine

# Instala dependências básicas, Nginx e Supervisor (para rodar Nginx e PHP juntos)
RUN apk add --no-cache \
    nginx \
    curl \
    unzip \
    sqlite-dev \
    supervisor \
    && docker-php-ext-install pdo_mysql pdo_sqlite bcmath opcache

# Define o diretório de trabalho padrão do servidor web
WORKDIR /var/www/html

# Copia os arquivos já compilados da action direto para a imagem
COPY . /var/www/html/

# Ajusta as permissões dos arquivos copiados
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configuração minimalista do Nginx otimizada para PHP (apontando para a pasta serve)
# Usamos __PORT__ como um marcador que será substituído na hora que o container rodar
RUN echo 'server { \
    listen __PORT__; \
    root /var/www/html/serve; \
    index index.php index.html; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}' > /etc/nginx/http.d/default.conf

# Configuração do Supervisord para iniciar o Nginx e o PHP-FPM ao mesmo tempo
RUN echo '[supervisord] \
nodaemon=true \
[program:php-fpm] \
command=php-fpm -F \
stdout_logfile=/dev/stdout \
stdout_logfile_maxbytes=0 \
stderr_logfile=/dev/stderr \
stderr_logfile_maxbytes=0 \
[program:nginx] \
command=nginx -g "daemon off;" \
stdout_logfile=/dev/stdout \
stdout_logfile_maxbytes=0 \
stderr_logfile=/dev/stderr \
stderr_logfile_maxbytes=0' > /etc/supervisord.conf

# Define a porta padrão como 80 caso nenhuma seja passada
ENV PORT=80

# Inicia trocando a porta no Nginx de acordo com a variável PORT e depois chama o Supervisor
CMD sh -c "sed -i \"s/__PORT__/${PORT}/g\" /etc/nginx/http.d/default.conf && /usr/bin/supervisord -c /etc/supervisord.conf"