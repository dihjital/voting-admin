# Use an official PHP runtime as a parent image
FROM php:8.3-fpm

# Set the working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    nginx \
    supervisor \ 
    gnupg \
    libmagickwand-dev \
    libmagickcore-dev \
    imagemagick \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_sqlite

# Manually clone, build, and install Imagick for PHP 8.3
RUN git clone --depth 1 https://github.com/Imagick/imagick /usr/src/php/ext/imagick \
    && docker-php-ext-install imagick \
    && docker-php-ext-enable imagick

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash - && apt-get install -y nodejs

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the current directory contents into the container at /var/www
COPY . .

# Install any needed packages specified in composer.json
RUN composer install

# Install npm dependencies
RUN npm install

# Set permissions for storage, bootstrap/cache, and database directories
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/database

# Copy the Nginx configuration file
COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Copy the supervisor configuration file and create the log directory
COPY .docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /var/log/supervisor && chmod -R 777 /var/log/supervisor

# Clean up unnecessary files to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Expose ports for web and vite
EXPOSE 80 5173