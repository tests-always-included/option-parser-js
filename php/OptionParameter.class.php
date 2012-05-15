<?php

class OptionParameter {
	const ARGUMENT_NONE = 0;
	const ARGUMENT_REQUIRED = 1;
	const ARGUMENT_OPTIONAL = 2;
	protected $actionCallback = null;
	protected $argumentName = null;
	protected $argumentRequired = self::ARGUMENT_NONE;
	protected $getoptFormat = array();
	protected $optionHelp = null;
	protected $optionLong = array();
	protected $optionShort = array();
	protected $validationCallback = null;
	protected $valuesParsed = array();

	public function __construct($short, $long, $message = null) {
		if ($short) {
			$this->optionShort = (array) $short;
		}

		if ($long) {
			$this->optionLong = (array) $long;
		}

		if ($message) {
			$this->optionHelp = $message;
		}
	}

	public function action($runthis) {
		$this->validateCallback($runthis);
		$this->actionCallback = $runthis;
		return $this;
	}

	public function argument($name, $required = true) {
		$this->argumentName = $name;

		if ($required) {
			$this->argumentRequired = self::ARGUMENT_REQUIRED;
		} else {
			$this->argumentRequired = self::ARGUMENT_OPTIONAL;
		}

		return $this;
	}

	public function count() {
		return count($this->valuesParsed);
	}

	public function handleArgument($option, $value = null) {
		if (! is_null($value) && $this->validationCallback) {
			$result = call_user_func($this->validationCallback, $value);

			if (! is_null($result)) {
				$exception = new Exception($result);
				$exception->argument = $this;
				throw $exception;
			}
		}

		if ($this->actionCallback) {
			call_user_func($this->actionCallback, $value);
		}

		$this->valuesParsed[] = $value;
		$getoptValue = false;

		if ($this->argumentRequired != self::ARGUMENT_NONE && ! is_null($value)) {
			$getoptValue = $value;
		}

		if (! array_key_exists($option, $this->getoptFormat)) {
			$this->getoptFormat[$option] = $getoptValue;
		} else {
			if (! is_array($this->getoptFormat[$option])) {
				$this->getoptFormat[$option] = array($this->getoptFormat[$option]);
			}

			$this->getoptFormat[$option][] = $getoptValue;
		}

		return $this;
	}

	public function getopt() {
		return $this->getoptFormat;
	}

	protected function getWidth() {
		static $cached = null;

		if (! is_null($cached)) {
			return $cached;
		}

		$output = `tput cols`;

		if ($output > 0) {
			$cached = $output * 1;
			return $cached;
		}

		$output = `stty -a`;
		
		if (preg_match('/ columns ([0-9]+)/', $output, $matches)) {
			if ($matches[1] > 0) {
				$cached = $matches[1] * 1;
				return $cached;
			}
		}

		$cached = 80;
		return $cached;
	}

	public function help($pad = 16, $gutter = 2, $width = null) {
		$options = array();
		$buffer = '';

		if (is_null($width)) {
			$width = $this->getWidth() - $gutter;
		}

		if (is_null($this->optionHelp)) {
			return '';
		}

		foreach ($this->optionShort as $o) {
			$options[] = '-' . $o;
		}

		foreach ($this->optionLong as $o) {
			$options[] = '--' . $o;
		}

		$buffer = implode(', ', $options);

		if ($this->argumentRequired == self::ARGUMENT_REQUIRED) {
			$buffer .= ' ' . $this->argumentName;
		} elseif ($this->argumentRequired == self::ARGUMENT_OPTIONAL) {
			$buffer .= '[=' . $this->argumentName . ']';
		}

		$help = $this->wrap(str_repeat(' ', $pad) . $this->optionHelp, $pad, $width);

		if (strlen($buffer) > $pad - $gutter) {
			$help = $this->wrap($buffer, 0, $width) . $help;
		} else {
			$help = $buffer . substr($help, strlen($buffer));
		}

		return $help;
	}

	public function matchAutocomplete($arg) {
		$hits = array();

		foreach ($this->optionLong as $o) {
			if (substr($o, 0, strlen($arg)) == $arg) {
				$hits[$o] = $this;
			}
		}

		return $hits;
	}

	public function matchLongArgument($arg) {
		return in_array($arg, $this->optionLong);
	}

	public function matchShortArgument($arg) {
		return in_array($arg, $this->optionShort);
	}

	public function matchWildcard() {
		if (in_array('*', $this->optionShort)) {
			return true;
		}

		if (in_array('-', $this->optionShort)) {
			return true;
		}

		return false;
	}

	public function usesArgument() {
		return $this->argumentRequired;
	}

	protected function validateCallback($runthis) {
		if (is_callable($runthis)) {
			return;
		}

		throw new Exception('Invalid callback or closure specified');
	}

	public function validation($runthis) {
		$this->validateCallback($runthis);
		$this->validationCallback = $runthis;
		return $this;
	}

	public function value() {
		$ret = $this->values();

		if (is_array($ret)) {
			$ret = end($ret);
		}

		return $ret;
	}

	public function values() {
		if (is_null($this->argumentName)) {
			return $this->count();
		}

		return $this->valuesParsed;
	}

	public function wrap($str, $pad, $width) {
		$lines = array();
		$str = rtrim($str);
		$spaces = str_repeat(' ', $pad);

		while (strlen($str)) {
			$newline = false;
			$newlineTest = null;

			foreach (array("\r\n", "\n", "\r") as $nlchars) {
				if ($newline === false) {
					$newline = strpos($str, $nlchars);
					$newlineTest = $nlchars;
				}
			}

			if ($newline !== false && $newline <= $width) {
				$lines[] = substr($str, 0, $newline);
				$str = substr($str, $newline + strlen($newlineTest));
			} else {
				$line = substr($str, 0, $width);
				$spacePos = strrpos($line, ' ');

				if ($spacePos > $width * 0.8) {
					$line = substr($line, 0, $spacePos + 1);
				}

				$str = substr($str, strlen($line));
				$lines[] = rtrim($line);
			}

			if (strlen($str)) {
				$str = $spaces . $str;
			}
		}

		return implode("\n", $lines) . "\n";
	}
}
