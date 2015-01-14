FROM centos:centos6
MAINTAINER Ondrej Hlavacek <ondrej.hlavacek@keboola.com>

RUN yum -y --enablerepo=epel,remi,remi-php55 install git php php-cli php-common php-mbstring

# Adding the default files
RUN cd /home && git clone https://github.com/keboola/docker-demo.git ./
RUN cd /home && curl -sS https://getcomposer.org/installer | php && php composer.phar install

ENTRYPOINT cd /home && php ./src/script.php --data=/data