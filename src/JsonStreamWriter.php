<?php

class JsonStreamWriter {
	private $handle;
	private $stateStack = array();

	public function __construct($handle) {
		$this->handle = $handle;
	}

	public function handleAny($item) {
		$type = gettype($item);

		switch ($type) {
			case 'boolean':
				$this->push($item ? 'true' : 'false');
			break;
			case 'string':
				$this->handleString($item);
			break;
			case 'integer':
			case 'double':
				$this->push($item);
			break;
			case 'NULL': // uppercase, of course
				$this->push('null');
			break;
			case 'array':
			case 'object':
				// PHP is such a bastardized language, in which you cannot distinguish lists,
				// maps and sets from each other. we now have to resort to guessing what the user meant:
				if ($type === 'array' && self::looksLikeAList($item)) {
					$this->handleArray($item);
				} else {
					$this->handleObject($item);
				}
			break;
			default:
				throw new \Exception('Unknown type: ' . $type);
		}
	}

	public function handleObject($obj) {
		$this->push('{');

		$first = true;

		foreach ($obj as $key => $value) {
			if (!$first) {
				$this->push(', ');
			} else {
				$first = false;
			}

			$this->handleString($key);

			$this->push(': ');

			$this->handleAny($value);
		}

		$this->push('}');
	}

	public function handleArray($arr) {
		$this->arrayBegin();

		foreach ($arr as $item) {
			$this->arrayItem($item);
		}

		$this->arrayEnd();
	}

	public function arrayBegin() {
		$this->push("[\n");

		array_push($this->stateStack, array('firstItem' => true));
	}

	public function arrayItem($item) {
		$stateStackIdx = count($this->stateStack) - 1;

		$firstItem = $this->stateStack[$stateStackIdx]['firstItem'];

		if ($firstItem) { // flip
			$this->stateStack[$stateStackIdx]['firstItem'] = false;
		} else {
			$this->push(", \n");
		}

		$this->handleAny($item);
	}

	public function arrayEnd() {
		$this->push("\n]\n");

		array_pop($this->stateStack);
	}

	public function handleString($val) {
		$val_escaped = str_replace(
			array('\\', '"', "\r", "\n", "\t"),
			array('\\\\', '\\"', '\\r', '\\n', '\\t'),
			$val
		);

		$this->push('"' . $val_escaped . '"');
	}

	private function push($chunk) {
		// fwrite() is buffered, so it is ok throw small chunks at it
		fwrite($this->handle, $chunk);
	}

	private static function looksLikeAList($arr) {
		$itemCount = count($arr);

		// looks like a list if array empty OR there exists a key at indices 0 and itemCount - 1
		return $itemCount === 0 || (isset($arr[0]) && isset($arr[$itemCount - 1]));
	}
}
