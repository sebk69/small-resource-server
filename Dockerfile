FROM php:8.4-cli-bookworm

ARG timezone="Europe/Paris"

# Paquets système (supervisor, libs de build, utilitaires)
RUN apt-get update && apt-get install -y --no-install-recommends \
      supervisor curl ca-certificates git unzip libssl-dev libcurl4-openssl-dev zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP nécessaires (pdo_mysql, etc.)
RUN docker-php-ext-install pdo_mysql

# Installe Swoole
RUN apt update && \
    apt install -y openssl \
       libssl-dev \
       libcurl4-openssl-dev \
       libbrotli-dev \
       build-essential && \
       rm -rf /var/lib/apt/lists/*
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# Install gd for coverage badge
RUN apt-get update && apt-get install -y zlib1g-dev libpng-dev libfreetype-dev fonts-liberation2
RUN docker-php-ext-configure gd --with-freetype
RUN docker-php-ext-install gd

# install pcov
RUN pecl install pcov && docker-php-ext-enable pcov

# install composer
RUN apt update && \
    apt install -y libzip-dev
RUN docker-php-ext-install zip
RUN git config --global --add safe.directory /app
RUN apt update && apt install -y zip git
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer

# Set timezone
RUN cp /usr/share/zoneinfo/$timezone /etc/localtime \
    && echo "$timezone" > /etc/timezone \
    && echo "[Date]\ndate.timezone=$timezone" > /usr/local/etc/php/conf.d/timezone.ini

# Répertoire application
COPY --chown=www-data:www-data . /app
WORKDIR /app
RUN composer install

# (Optionnel) Paramètres Swoole
# Désactive les short names si vous préférez l'espace de noms complet \Swoole\Coroutine
ENV swoole.use_shortname=Off

# Supervisor
#   - /etc/supervisor/conf.d/swoole.conf : programme Swoole
#   - /var/log/supervisor : logs
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor
RUN chmod 700 /app/bin/*
RUN chown www-data:www-data /app/bin

# Copie la configuration supervisor fournie ci-dessous
COPY conf/supervisor.conf /etc/supervisor/conf.d/swoole.conf

# Port HTTP du serveur Swoole
EXPOSE 9501

# Healthcheck simple (attend que le serveur réponde)
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
  CMD curl -fsS http://127.0.0.1:9501/health || exit 1

# Lancement de supervisor en premier plan
CMD service supervisor start && sleep infinity
