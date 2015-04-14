# VERSION 1.0.5
FROM keboola/base-php
MAINTAINER Ondrej Hlavacek <ondrej.hlavacek@keboola.com>

WORKDIR /home

# Initialize 
RUN git clone https://github.com/keboola/docker-demo.git ./
RUN composer install --no-interaction

ENTRYPOINT php ./src/script.php --data=/data
