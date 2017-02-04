What can sql2json help you with?
================================

This project can help you if you need to:

- Export data from a database into JSON so it can be transformed to some other format more easily. E.g. you need to transform
  a database from another vendor to another (e.g. MySQL -> PostgreSQL). sql2json would help you get the data from MySQL into JSON
  and after that you could write a program to insert the data into PostgreSQL. This project doesn't help you with the actual
  transformation because it's not a trivial problem.

- Just dump the database contents to JSON so the JSON can be accessed from any programming language.

Why not just export SQL dump from vendor X and import the same file to vendor Y - SQL is standard anyways?

Turns out that SQL - even though being a standard, is not that interoperable. You cannot take a SQL dump produced
by say MySQL, and import it into SQLite or PostgreSQL. There are [clever hacks](https://gist.github.com/esperlu/943776),
but you're going to get disappointed.

Out of this frustration I created sql2json, that essentially dumps your database as JSON files, so the dataset is super
easy to process (or import to another database) in any programming language! SQL is hard to parse while JSON is super trivial.


Walkthroughs
------------

- [mysql](docs/walkthrough_mysql.md) (read this to get best sense of how this works)
- [postgresql](docs/walkthrough_postgresql.md)
- [sqlite](docs/walkthrough_sqlite.md)


Architecture
------------

sql2json is just a tool (Docker container) that exports either:

- a database (from a running DBMS) OR
- .sql file (you run a temporary MySQL/PostgreSQL/.. instance with help of Docker) to JSON files - one per table.

Supported databases:

	     +-------+
	     |       |
	     | MySQL +---+
	     |       |   |
	     +-------+   |
	                 |
	    +--------+   |     +----------+     +-------------+
	    |        |   |     |          |     |             |
	    | SQLite +---------> sql2json +-----> .json files |
	    |        |   |     |          |     |             |
	    +--------+   |     +----------+     +-------------+
	                 |
	+------------+   |
	|            |   |
	| PostgreSQL +---+
	|            |
	+------------+

View from Docker's perspective:

	Database container ------+   sql2json container --+
	|                        |   |                    |
	| +-----------+   +----+ |   | +-----------+      |
	| |Import data|-->|DBMS|<------| sql2json  |      |
	| +-----------+   +----+ |   | +-----------+      |
	|     ^                  |   |   |                |
	+-----|------------------+   +---|----------------+
	      |                          |
	+-----|--------------------------|-----+
	|     |                          v     |
	|  +--------+    +-------------------+ |
	|  |SQL dump|    |Result:            | |
	|  +--------+    |data in .json files| |
	|                +-------------------+ |
	|                                      |
	Docker host ---------------------------+

The end result is pretty nice, as from host perspective the only tool required is Docker. And you don't have to permanently
install MySQL/PostgreSQL if all you want is to load the .sql dump and transform it to JSON.
Just remove the temporary DBMS and sql2json containers when you're done and your system is clean as ever. :)

Demo
====

[![asciicast](https://asciinema.org/a/722yo4odqo1sulztyaeaxz4k1.png)](https://asciinema.org/a/722yo4odqo1sulztyaeaxz4k1)


FAQ
---

Q: Can I export only a subset of the data with a custom SQL query?

A: Yes, [see this link for instructions](https://github.com/function61/sql2json/issues/1)!

-----------

Q: My database isn't supported

A: Adding other databases is really easy, provided that PHP's [PDO layer](http://php.net/manual/en/pdo.drivers.php)
supports it! PR's are appreciated!

-----------

Q: I have a large database - will my data fit in memory?

A: No problem, sql2json streams the JSON output and gzips the output files (constant RAM usage and low disk usage).

-----------

Q: How do I read my table.json.gz file if it doesn't fit in RAM?

A: The answer is streaming JSON parsing. The same applies for any huge file: you will not process it as a buffered whole, but in parts.

In XML world there is a concept of a SAX parsing (= Streaming API for XML).
There are streaming JSON parsers for almost any language (these are just examples - there might exist better alternatives):

- PHP: [salsify/jsonstreamingparser](https://github.com/salsify/jsonstreamingparser)
- Ruby: [dgraham/json-stream](https://github.com/dgraham/json-stream)
- JavaScript: [dscape/clarinet](https://github.com/dscape/clarinet)

Or write ad-hoc one yourself (it isn't too hard in this case - sql2json intentionally writes one row per line)


Thanks
======

[Chinook example dataset](http://chinookdatabase.codeplex.com/): see `example_dataset/` directory.
(NOTE: I had to convert PostgreSQL's .sql file to utf-8)

Todo
====

- Add ODBC support (should be easy to hack in, but I didn't have any ODBC database driver installed)


Support / contact
-----------------

Basic support (no guarantees) for issues / feature requests via GitHub issues.

Paid support is available via [function61.com/consulting](https://function61.com/consulting/)

Contact options (email, Twitter etc.) at [function61.com](https://function61.com/)
