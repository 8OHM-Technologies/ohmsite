# ==============================================================================
# STAGE 1: Build Frontend Assets
# ==============================================================================
FROM node:24-alpine AS frontend_builder

WORKDIR /app

# Copy configuration files for package managers
COPY package*.json ./

# Install frontend dependencies cleanly
RUN npm ci

# Copy the rest of the application files to compile assets
COPY . .

# Compile assets using Vite or Laravel Mix
RUN npm run build


# ==============================================================================
# STAGE 2: Final Application Image
# ==============================================================================
FROM php:8.4-fpm

# Define working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev

# Clear apt cache to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions for Laravel (including PostgreSQL support)
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a system user to match local user permissions (prevents file lock issues)
RUN useradd -G sudo,www-data -u 1000 -d /home/dev dev
RUN mkdir -p /home/dev/.composer && chown -R dev:dev /home/dev

# Set permissions and copy Laravel source files
COPY --chown=dev:www-data . /var/www

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy the compiled assets from the frontend build stage
COPY --from=frontend_builder --chown=dev:www-data /app/public/build /var/www/public/build

# Set explicit execution permissions for Laravel cache and storage directories
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Switch to the non-root user
USER dev

EXPOSE 9000
CMD ["php-fpm"]
