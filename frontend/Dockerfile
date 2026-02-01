# Build stage
FROM node:20-alpine AS builder

WORKDIR /app

# Copy package files
COPY package.json ./

# Install dependencies
RUN npm install

# Copy source code
COPY . .

# Build argument for API URL
ARG VITE_API_URL=http://localhost:8080
ENV VITE_API_URL=${VITE_API_URL}

# Build the application
RUN npm run build

# Production stage with OpenLiteSpeed
FROM litespeedtech/openlitespeed:1.7.19-lsphp82

# Remove default PHP installation (not needed for static files)
RUN rm -rf /var/www/vhosts/localhost/html/*

# Copy built files from builder
COPY --from=builder /app/dist /var/www/html

# Copy OpenLiteSpeed configuration
COPY config/lsws/httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
COPY config/lsws/vhconf.conf /usr/local/lsws/conf/vhosts/localhost/vhconf.conf

# Create vhost directory
RUN mkdir -p /usr/local/lsws/conf/vhosts/localhost

# Set permissions
RUN chown -R nobody:nogroup /var/www/html

EXPOSE 3000

CMD ["/usr/local/lsws/bin/litespeed", "-d"]
