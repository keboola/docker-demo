# VERSION 1.0.4
FROM keboola/base-php
MAINTAINER Ondrej Hlavacek <ondrej.hlavacek@keboola.com>

WORKDIR /home

# Initialize 
RUN git clone https://github.com/keboola/docker-demo.git ./
RUN composer install

ENTRYPOINT php ./src/script.php --data=/data
