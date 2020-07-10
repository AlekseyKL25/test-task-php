FROM mariadb:latest

RUN apt-get update && apt-get install -y \
    bash \
    curl \
    htop \
    iputils-ping \
    nano \
    wget

COPY ./conf.d/* /etc/mysql/conf.d

EXPOSE 3306