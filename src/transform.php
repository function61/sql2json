<?php
require 'JsonStreamWriter.php';

define('DATA_DIR', '/result/data');
define('SCHEMA_DIR', '/result/schema');

// for these operations there are no DBMS agnostic way of doing these,
// so we'll have to write adapters..
interface DbAdapter {
	public function selectSql($table);

	public function listTables($conn);
}

class PostgresAdapter implements DbAdapter {
	public function selectSql($table) { return "SELECT * FROM \"$table\""; }

	public function listTables($conn) {
		$sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";

		$query = $conn->query($sql);
		$query->execute();

		$tables = $query->fetchAll(PDO::FETCH_COLUMN, 0);

		return $tables;
	}
}

class MysqlAdapter implements DbAdapter {
	// with MySQL we cannot use quotes. backticks would probably work IIRC
	public function selectSql($table) { return "SELECT * FROM $table"; }

	public function listTables($conn) {
		$sql = "SHOW TABLES";

		$query = $conn->query($sql);
		$query->execute();

		$tables = $query->fetchAll(PDO::FETCH_COLUMN, 0);

		return $tables;
	}
}

class SqliteAdapter implements DbAdapter {	
	public function selectSql($table) { return "SELECT * FROM \"$table\""; }

	public function listTables($conn) {
		$sql = "SELECT name FROM sqlite_master WHERE type='table'";

		$query = $conn->query($sql);
		$query->execute();

		$tables = $query->fetchAll(PDO::FETCH_COLUMN, 0);

		return $tables;
	}
}

$matches = null;

// DSN environment variable format:
//     <username>,<password>,<pdo_dsn>
if (!preg_match('/^([^,]*),([^,]*),(.+)$/', getenv('DSN'), $matches)) {
	throw new \Exception('Failed to parse DSN environment variable');
}

$username = $matches[1];
$password = $matches[2];
$dsn = $matches[3];

function makeDirectoryIfNotExists($path) {
	if (!file_exists($path)) {
		if (!mkdir($path)) {
			throw new \Exception('Failed to mkdir(): ' . $path);
		}
	}
}

makeDirectoryIfNotExists(DATA_DIR);
makeDirectoryIfNotExists(SCHEMA_DIR);

function info($message) {
	$date = date('Y-m-d H:i:s');

	print "$date - $message\n";
}

class CompressingJsonStreamFileWriter {
	public $writer;

	private $handle;
	private $path;

	public function __construct($path) {
		$this->path = $path;

		// FIXME: specify level 4-5 for compression ratio
		$specialWrapper = strpos($this->path, '.gz') !== false ? 'compress.zlib://' : '';

		$this->handle = fopen($specialWrapper . $this->path, 'w');
		if (!$this->handle) {
			throw new \Exception('Failed to fopen(..., "w"): ' . $specialWrapper . $this->path);
		}

		$this->writer = new JsonStreamWriter($this->handle);		
	}

	public function getPath() {
		return $this->path;
	}

	public function close() {
		fclose($this->handle);
	}
}

function writeSchema($conn, $tables) {
	info('Fetching schema');

	$combinedSchema = array();

	foreach ($tables as $tableName) {
		$tableFields = array();

		$query = $conn->query('DESCRIBE ' . $tableName);
		$query->execute();

		while ($columnDescription = $query->fetch(PDO::FETCH_ASSOC)) {
			$tableFields[] = $columnDescription;
		}

		$tables = $query->fetchAll(PDO::FETCH_COLUMN, 0);

		$oneTableSchema = array('name' => $tableName, 'fields' => $tableFields);

		$combinedSchema[] = $oneTableSchema;

		file_put_contents('/result/schema/' . $tableName . '.json', json_encode($oneTableSchema, JSON_PRETTY_PRINT));
		info('Wrote /result/schema/' . $tableName . '.json');
	}

	file_put_contents('/result/combined_schema.json', json_encode($combinedSchema, JSON_PRETTY_PRINT));
	info('Wrote /result/combined_schema.json');
}

function dumpSql($conn, $sql, $filename) {
	$query = $conn->query($sql);
	$query->execute();

	$json = new CompressingJsonStreamFileWriter($filename);
	$json->writer->arrayBegin();

	$rowCount = 0;

	while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
		$json->writer->arrayItem($row);

		$rowCount++;
	}

	$json->writer->arrayEnd();

	$json->close();

	info('Wrote ' . $rowCount . ' rows to ' . $json->getPath());
}

function dumpTable($conn, $adapter, $table) {
	info('Dumping ' . $table);

	dumpSql($conn, $adapter->selectSql($table), '/result/data/' . $table . '.json.gz');
}

info($username !== '' ? 'Using username: ' . $username : 'Username: (no username)');
info($password !== '' ? 'Using password: ********' : 'Password: (no password)');
info('Connecting to DSN ' . $dsn);

$conn = new PDO($dsn, $username, $password, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
));

$driverName = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);

$adapter = null;

switch ($driverName) {
	case 'sqlite':
		$adapter = new SqliteAdapter();
	break;
	case 'mysql':
		$adapter = new MysqlAdapter();
	break;
	case 'pgsql':
		$adapter = new PostgresAdapter();
	break;
	default:
		throw new \Exception("Unsupported driver: $driverName");
	break;
}

info('Listing tables');
$tables = $adapter->listTables($conn);

if ($adapter instanceof MysqlAdapter) {
	writeSchema($conn, $tables);
} else {
	info('Skipping schema fetch - only know how to do it for MySQL');
}

$customSql = getenv('SQL');

if ($customSql) {
	info("Custom SQL statement specified: $customSql");

	dumpSql($conn, $customSql, '/result/data/custom_' . time() . '.json.gz');
}
else {
	foreach($tables as $table) {
		dumpTable($conn, $adapter, $table);
	}

	info('Exported ' . count($tables) . ' tables');
}

info('Done');
