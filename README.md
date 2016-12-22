What?
=====

You need to transform a database from another vendor to another (MySQL -> PostgreSQL), or to just access the dataset programmatically?

Turns out that SQL - even though being a standard, is not that interoperable. You cannot take a SQL dump produced
by say MySQL, and import it into SQLite or PostgreSQL. There are [clever hacks](https://gist.github.com/esperlu/943776),
but you're going to get disappointed.

Out of the frustration I created sql2json, that essentially dumps your database as JSON files, so the dataset is super
easy to process (or import to another database) in any programming language! SQL is hard to parse while JSON is super trivial.

sql2json is just a tool (Docker container) that exports a database (either a running DBMS) or .sql file (you run a
temporary MySQL/PostgreSQL/so on instance with help of Docker) to JSON files - one per table.

You get the best sense of what this tool does just by diving into the [MySQL export walkthrough](docs/walkthrough_mysql.md).

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

Walkthroughs
============

- [mysql](docs/walkthrough_mysql.md)
- [postgresql](docs/walkthrough_postgresql.md)
- [sqlite](docs/walkthrough_sqlite.md)


FAQ
---

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

[Chinook example dataset](http://chinookdatabase.codeplex.com/): see `examples/` directory.
(NOTE: I had to convert PostgreSQL's .sql file to utf-8)

Todo
====

- asciinema recording?
- Add ODBC support (should be easy to hack in, but I didn't have any ODBC database driver installed)
- Advertise here: http://stackoverflow.com/questions/5036605/how-to-export-a-mysql-database-to-json
