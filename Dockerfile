FROM centos:centos6
MAINTAINER Ondrej Hlavacek <ondrej.hlavacek@keboola.com>

RUN yum -y --enablerepo=epel,remi,remi-php55 install php php-cli php-common php-mbstring

# Adding the default files
ADD script.php /home/script.php
ADD composer.json /home/composer.json
ADD composer.lock /home/composer.lock

RUN cd home && curl -sS https://getcomposer.org/installer | php && php composer.phar install

ENTRYPOINT cd home && php ./script.php