FROM litespeedtech/openlitespeed:1.7.19-lsphp82

# Install additional PHP extensions and tools
RUN apt-get update && apt-get install -y \
    lsphp82-mysql \
    lsphp82-intl \
    lsphp82-curl \
    lsphp82-common \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts || true

# Copy application code
COPY . .

# Run composer scripts after copying all files
RUN composer install --no-dev --optimize-autoloader

# Create necessary directories
RUN mkdir -p var/cache var/log && \
    chown -R nobody:nogroup var

# Copy OpenLiteSpeed configuration
COPY config/lsws/httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
COPY config/lsws/vhconf.conf /usr/local/lsws/conf/vhosts/localhost/vhconf.conf

# Create vhost directory if it doesn't exist
RUN mkdir -p /usr/local/lsws/conf/vhosts/localhost

# Set permissions
RUN chown -R nobody:nogroup /var/www/html

EXPOSE 8080

# Start OpenLiteSpeed
CMD ["/usr/local/lsws/bin/litespeed", "-d"]
