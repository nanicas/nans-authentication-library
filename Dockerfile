# Use a imagem oficial do PHP 8.2
FROM php:8.2

# Atualizar e instalar pacotes necessários
RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    unzip \
    git \
    zip

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Definir o diretório de trabalho
WORKDIR /var/www/html
