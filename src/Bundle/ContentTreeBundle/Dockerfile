FROM php:7.4-cli

RUN apt-get update \
 && apt-get install -y git

# install composer
RUN curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /home
WORKDIR /home