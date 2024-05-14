FROM php:apache-bookworm
MAINTAINER lasy <lasyard@yeah.net>

RUN echo "ServerName lasys" >> /etc/apache2/apache2.conf \
    && /usr/sbin/a2enmod rewrite

# Copy files
WORKDIR /var/www
COPY docker html
WORKDIR /var/www/html
COPY src lasys/src
COPY vendor/erusev/parsedown/Parsedown.php lasys/vendor/erusev/parsedown/Parsedown.php
COPY pub pub/sys
COPY README.md data/README.md
