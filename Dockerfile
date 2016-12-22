# this comes unmodified from the official Alpine repo, but I mirror this image
# to pin the exact image for reproducability
FROM joonas/alpine:f4fddc471ec2

RUN apk add --update php-cli php-json php-pdo php-pdo_mysql php-pdo_odbc php-pdo_pgsql php-pdo_sqlite php-zlib sqlite \
  && rm -rf /var/cache/apk/*

CMD ["php","/transformer/transform.php"]

ADD ["/src","/transformer"]
