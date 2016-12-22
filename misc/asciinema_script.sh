
PS1='\[\033[0;33m\]~/sql2json \$ \[\033[0m\]'


# here's the .sql file we have that we would like to export as JSON
cat example_dataset/Chinook_PostgreSql.sql.gz | gzip -d | head -30

# first we'll launch Postgres instance so we can import the data there
docker run -d --name sql2json-dbserver -p 5432:5432 kiasaki/alpine-postgres:9.5

# then we'll create an empty database
echo 'CREATE DATABASE chinook' | docker exec -i sql2json-dbserver psql -U postgres postgres

# now we'll import the data into it
cat example_dataset/Chinook_PostgreSql.sql.gz | gzip -d | docker exec -i sql2json-dbserver psql -U postgres chinook > /dev/null

# let's find out the IP address of the database server
docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' sql2json-dbserver

# create directory for the resulting JSON files
mkdir sql2json_result

# and now export the database as JSON
docker run --rm -it -v "$(pwd)/sql2json_result:/result" -e "DSN=postgres,,pgsql:dbname=chinook;host=172.17.0.2;port=5432" joonas/sql2json

# take a look at the resulting file structure
tree sql2json_result/

# now you can work with the dataset from the commandline
cat sql2json_result/data/Album.json.gz | gzip -d | jq '.[0]'

# even sort the whole table by "Title" column
cat sql2json_result/data/Album.json.gz | gzip -d | jq '.[].Title' | sort | head -10

# destroy the database instance
docker rm -f sql2json-dbserver

# thanks for watching!
