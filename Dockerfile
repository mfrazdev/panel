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

# Recebe a versão gerada pela Action e usa para baixar a release
ARG PANEL_VERSION=latest
ENV PANEL_VERSION=${PANEL_VERSION}

# Baixa o panel.zip direto das suas releases usando a versão atual da compilação
RUN curl -L -o panel.zip "https://github.com/murillo-frazao-cunha/panel/releases/download/${PANEL_VERSION}/panel.zip" \
    && unzip -q panel.zip -d . \
    && rm panel.zip \
    && chown -R www-data:www-data /var/www/html \
    && mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configuração minimalista do Nginx otimizada para PHP (apontando para a pasta serve)
RUN echo 'server { \
    listen 80; \
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

# Expõe a porta 80 do painel
EXPOSE 80

# Inicia o Supervisor que vai gerenciar o Nginx e o PHP
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]