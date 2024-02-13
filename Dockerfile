FROM php:7.4-apache

MAINTAINER susilon <studiopiapia@gmail.com>

# Install apt-utils, libzip
RUN apt-get update \
	&& apt-get install -y apt-utils libzip-dev

# Install Mcrypt
RUN apt-get update \
	&& apt-get install -y libmcrypt-dev \
    && pecl install mcrypt-1.0.3 \
	&& docker-php-ext-enable mcrypt

# Install GD
RUN apt-get update \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install gd

# Install Mysql
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install opcache
RUN docker-php-ext-install opcache

# Install PHP zip extension
RUN docker-php-ext-install zip

# Configure Apache Document Root
ENV APACHE_DOC_ROOT /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install MySQL CLI Client
RUN apt-get update \
    && apt-get install -y mariadb-client

# uncomment if you need sql server connection
#ENV ACCEPT_EULA=Y
#RUN apt-get update && apt-get install -y gnupg2
#RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - 
#RUN curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list 
#RUN apt-get update 
#RUN ACCEPT_EULA=Y apt-get -y --no-install-recommends install msodbcsql17 unixodbc-dev 
#RUN pecl install sqlsrv
#RUN pecl install pdo_sqlsrv
#RUN docker-php-ext-enable sqlsrv pdo_sqlsrv

# comment line below if you don't want to bundle the application
COPY . /var/www/html/