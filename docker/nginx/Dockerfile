FROM nginx:1.27-alpine

WORKDIR /var/www/html

COPY public ./public

# Global nginx config — defines rate-limit zones, gzip, timeouts, server_tokens off
COPY docker/nginx/nginx.conf    /etc/nginx/nginx.conf
# Virtual-host config — upstream, security headers, PHP execution guard
COPY docker/nginx/default.conf  /etc/nginx/conf.d/default.conf

RUN mkdir -p /var/www/html/public/uploads/member_photos /var/www/html/public/uploads/checkin_photos \
    # Validate config at build time so bad syntax breaks the build rather than
    # silently falling back to the old config at runtime.
    && nginx -t -c /etc/nginx/nginx.conf
