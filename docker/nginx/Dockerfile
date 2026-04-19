FROM nginx:1.27-alpine

WORKDIR /var/www/html

COPY public ./public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

RUN mkdir -p /var/www/html/public/uploads/member_photos /var/www/html/public/uploads/checkin_photos
