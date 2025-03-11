FROM php:8.2-fpm


WORKDIR /var/www/

RUN apt-get update -y && apt-get install -y nginx-light
COPY . /var/www/
RUN chown -R www-data:www-data /var/www

ENTRYPOINT ["sh", "/var/www/docker/entrypoint.sh"]

EXPOSE 8080