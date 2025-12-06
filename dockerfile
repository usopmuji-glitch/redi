# Dockerfile (minimal - built-in PHP server)
FROM php:8.2-cli

WORKDIR /app

# Copia el código
COPY . /app

# Instala composer si no lo tienes
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && php -r "unlink('composer-setup.php');"

# Instala dependencias PHP (si composer.json existe)
RUN if [ -f composer.json ]; then composer install --no-dev --prefer-dist --no-interaction; fi

# Puerto por defecto (Render usa PORT env, default 10000)
ENV PORT=10000

# Expone (informativo)
EXPOSE 10000

# Start usando variable PORT y sirviendo carpeta 'public' si existe
CMD ["sh", "-c", "if [ -d public ]; then php -S 0.0.0.0:${PORT} -t public; else php -S 0.0.0.0:${PORT}; fi"]
