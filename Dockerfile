# Use a imagem oficial do PHP 8.3.22
FROM php:8.3.22

# Atualizar certificados e GPG antes de tudo
RUN apt-get update && apt-get install -y \
    ca-certificates \
    gnupg \
    dirmngr \
    tzdata

# Atualizar e instalar pacotes necessários
RUN apt-get install -y software-properties-common \
    libzip-dev \
    unzip \
    git

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Definir o diretório de trabalho
WORKDIR /var/www/html

# Mudar para usuário root
USER root
