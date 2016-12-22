
Convert .sql into SQLite's .db
==============================

```
$ mkdir sql2json_result
```

Place your .sql file in `sql2json_result/`.
(or the example `$ cat example_dataset/Chinook_Sqlite.sql.gz | gzip -d > sql2json_result/Chinook_Sqlite.sql`)

Now convert that into SQLite's database format file:

```
$ docker run --rm -it -v "$(pwd)/sql2json_result:/result" joonas/sql2json sqlite3 /result/temp.db
PRAGMA synchronous=OFF;
PRAGMA journal_mode=MEMORY;
BEGIN TRANSACTION;
.read /result/Chinook_Sqlite.sql
END TRANSACTION;
```

Now SQLite will import the SQL file. If you used the `Chinook_Sqlite.sql`, you're going to get one error like this:

```
Error: near line 1: near "": syntax error
```

Don't mind it - it's probably just confused about a blank line.

It's going to stay quiet for a while, but that means it's working. Just wait until you get `sqlite> ` prompt.

After it's done, hit `Ctrl + d` to exit from SQLite (and the container)

Now you should have `sql2json_result/temp.db`. That sql2json can directly dump into JSON. Move on to next heading.


I have an SQLite-formatted .db file now
=======================================

Now the database should be at `transformer_result/temp.db`.

```
$ docker run --rm -it -v "$(pwd)/sql2json_result:/result" -e "DSN=,,sqlite:/result/temp.db" joonas/sql2json
2016-12-21 22:10:55 - Username: (no username)
2016-12-21 22:10:55 - Password: (no password)
2016-12-21 22:10:55 - Connecting to DSN sqlite:/result/temp.db
2016-12-21 22:10:55 - Listing tables
2016-12-21 22:10:55 - Skipping schema fetch - only know how to do it for MySQL
2016-12-21 22:10:55 - Dumping Album
2016-12-21 22:10:55 - Wrote 347 rows to /result/data/Album.json.gz
2016-12-21 22:10:55 - Dumping Artist
2016-12-21 22:10:55 - Wrote 275 rows to /result/data/Artist.json.gz
2016-12-21 22:10:55 - Dumping Customer
2016-12-21 22:10:55 - Wrote 59 rows to /result/data/Customer.json.gz
2016-12-21 22:10:55 - Dumping Employee
2016-12-21 22:10:55 - Wrote 8 rows to /result/data/Employee.json.gz
2016-12-21 22:10:55 - Dumping Genre
2016-12-21 22:10:55 - Wrote 25 rows to /result/data/Genre.json.gz
2016-12-21 22:10:55 - Dumping Invoice
2016-12-21 22:10:55 - Wrote 412 rows to /result/data/Invoice.json.gz
2016-12-21 22:10:55 - Dumping InvoiceLine
2016-12-21 22:10:55 - Wrote 2240 rows to /result/data/InvoiceLine.json.gz
2016-12-21 22:10:55 - Dumping MediaType
2016-12-21 22:10:55 - Wrote 5 rows to /result/data/MediaType.json.gz
2016-12-21 22:10:55 - Dumping Playlist
2016-12-21 22:10:55 - Wrote 18 rows to /result/data/Playlist.json.gz
2016-12-21 22:10:55 - Dumping PlaylistTrack
2016-12-21 22:10:55 - Wrote 8715 rows to /result/data/PlaylistTrack.json.gz
2016-12-21 22:10:55 - Dumping Track
2016-12-21 22:10:56 - Wrote 3503 rows to /result/data/Track.json.gz
2016-12-21 22:10:56 - Done, exported 11 tables

```

Now scoot over to the MySQL variant of tutorial to see the rest!
