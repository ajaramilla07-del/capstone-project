FROM php:8.2-cli

WORKDIR /app

RUN docker-php-ext-install pdo pdo_mysql

COPY . .

CMD ["sh", "-c", "php database/setup.php && php -S 0.0.0.0:${PORT} -t ."]
