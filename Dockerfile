# VERSION 1.0.1
FROM centos:centos6
MAINTAINER Ondrej Hlavacek <ondrej.hlavacek@keboola.com>

# Image setup
WORKDIR /tmp
RUN yum -y --enablerepo=epel,remi,remi-php55 install git php php-cli php-common php-mbstring
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

WORKDIR /home

# Initialize 
RUN git clone https://github.com/keboola/docker-demo.git ./
RUN composer install

ENTRYPOINT php ./src/script.php --data=/data