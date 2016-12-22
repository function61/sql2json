
Overview
========

This guide is pretty much the same as for MySQL. Go read it first, and then come back! We'll only list here the stuff that's different.

<img src='http://g.gravizo.com/g?
  digraph G {
  	sql2json [label="sql2json process"];
  	export_from_where [shape=doubleoctagon label="Do you have a running database instance?"];
  	run_sql2json [label="Run sql2json"];
  	i_only_have_sql_file [label="I only have an .sql file"];
  	Done [label="Done! Data exported as .json files :%29"];
  	create_mysql_instance [label="Create database instance\n%28temporary, as Docker container%29"];
  	load_data_from_sql [label="Load .sql file into it"];
	sql2json -> export_from_where;
  	export_from_where -> run_sql2json [label="yes"];
  	export_from_where -> i_only_have_sql_file [label="no"];
  	i_only_have_sql_file -> create_mysql_instance -> load_data_from_sql -> run_sql2json;
  	run_sql2json -> Done;
  }
'>


I only have an .sql file
========================

So you have a .sql file. We first need to load it into a new PostgreSQL server instance
(we can conveniently do that with help of Docker):

```
$ docker run -d --name sql2json-dbserver -p 5432:5432 kiasaki/alpine-postgres:9.5
```

Ok the instance is running and ready to use, now we need to load the .sql file into it.

In this example we'll use the name `chinook` as the database name. If yours is different, replace accordingly.

First, create the database:

```
$ echo 'CREATE DATABASE chinook' | docker exec -i sql2json-dbserver psql -U postgres postgres
```

Now, let's load the data into that database:

```
$ cat example_dataset/Chinook_PostgreSql.sql.gz | gzip -d | docker exec -i sql2json-dbserver psql -U postgres chinook
```

(note: you don't need the gzip part unless your .sql file gzipped)

Ok, now we have a DBMS instance running with the data we want to export as JSON.

Let's find out the IP address of the DBMS:

```
$ docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' sql2json-dbserver
172.17.0.2
```

This particular image doesn't have a password configured, so the details we need to build the DSN are:

```
username=postgres
password=(none)
host=172.17.0.2 (remember to replace with your own details)
```

Therefore, our DSN is:

```
postgres,,pgsql:dbname=chinook;host=172.17.0.2;port=5432
```

If it would have a different user/password, the DSN is:

```
myusername,mysecret,pgsql:dbname=chinook;host=172.17.0.2;port=5432
```

For the record, our DSN format is:

```
<username>,<password>,<pdo_dsn>
```

Now we have created the database server and loaded the data, you can proceed to the
next heading which explains how to transform a database into JSON.


I have a database server instance I want to export data from
============================================================

Ok great, we just need the DSN and we're ready to do the export. If you don't know the DSN, read the previous heading.

In this example scenario, our DSN is:

```
postgres,,pgsql:dbname=chinook;host=172.17.0.2;port=5432
```

Let's create a directory, into which the .json files will be dumped:

```
$ mkdir sql2json_result
```

Now run the conversion process:

```
$ docker run --rm -it -v "$(pwd)/sql2json_result:/result" -e "DSN=postgres,,pgsql:dbname=chinook;host=172.17.0.2;port=5432" joonas/sql2json
2016-12-21 21:39:49 - Using username: postgres
2016-12-21 21:39:49 - Password: (no password)
2016-12-21 21:39:49 - Connecting to DSN pgsql:dbname=chinook;host=172.17.0.2;port=5432
2016-12-21 21:39:49 - Listing tables
2016-12-21 21:39:49 - Skipping schema fetch - only know how to do it for MySQL
2016-12-21 21:39:49 - Dumping Customer
2016-12-21 21:39:49 - Wrote 59 rows to /result/data/Customer.json.gz
2016-12-21 21:39:49 - Dumping Artist
2016-12-21 21:39:49 - Wrote 275 rows to /result/data/Artist.json.gz
2016-12-21 21:39:49 - Dumping PlaylistTrack
2016-12-21 21:39:49 - Wrote 8715 rows to /result/data/PlaylistTrack.json.gz
2016-12-21 21:39:49 - Dumping Track
2016-12-21 21:39:50 - Wrote 3503 rows to /result/data/Track.json.gz
2016-12-21 21:39:50 - Dumping Playlist
2016-12-21 21:39:50 - Wrote 18 rows to /result/data/Playlist.json.gz
2016-12-21 21:39:50 - Dumping Genre
2016-12-21 21:39:50 - Wrote 25 rows to /result/data/Genre.json.gz
2016-12-21 21:39:50 - Dumping Invoice
2016-12-21 21:39:50 - Wrote 412 rows to /result/data/Invoice.json.gz
2016-12-21 21:39:50 - Dumping Employee
2016-12-21 21:39:50 - Wrote 8 rows to /result/data/Employee.json.gz
2016-12-21 21:39:50 - Dumping Album
2016-12-21 21:39:50 - Wrote 347 rows to /result/data/Album.json.gz
2016-12-21 21:39:50 - Dumping InvoiceLine
2016-12-21 21:39:50 - Wrote 2240 rows to /result/data/InvoiceLine.json.gz
2016-12-21 21:39:50 - Dumping MediaType
2016-12-21 21:39:50 - Wrote 5 rows to /result/data/MediaType.json.gz
2016-12-21 21:39:50 - Done, exported 11 tables
```

Now scoot over to the MySQL variant of tutorial to see the rest!
