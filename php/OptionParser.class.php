<?php

require_once(__DIR__ . '/OptionParameter.class.php');

class OptionParser {
	protected $doAutocomplete = false;
	protected $doScanAll = true;
	protected $parameters = array();
	protected $parametersByName = array();
	protected $programNameParsed = null;

	public function __get($name) {
		return $this->get($name);
	}

	public function addOption($short, $long, $helpMessage = null, $name = null) {
		$p = new OptionParameter($short, $long, $helpMessage);
		$this->parameters[] = $p;

		if ($name) {
			$this->parametersByName[$name] = $p;
		}

		return $p;
	}

	public function autocomplete($boolean) {
		$this->doAutocomplete = !! $boolean;  // Force $boolean to be a boolean
		return $this;
	}

	public function get($name) {
		if (! $this->parametersByName[$name]) {
			throw new Exception('No parameter named ' . $name);
		}

		return $this->parametersByName[$name];
	}

	public function getopt() {
		$out = array();

		foreach ($this->parameters as $p) {
			foreach ($p->getopt() as $name => $value) {
				$out[$name] = $value;
			}
		}

		return $out;
	}

	public function getValue($name) {
		if (! $this->parametersByName[$name]) {
			throw new Exception('No parameter named ' . $name);
		}

		return $this->parametersByName[$name]->value();
	}

	public function help() {
		$out = '';

		foreach ($this->parameters as $p) {
			$out .= $p->help();
		}

		return $out;
	}

	public function helpAction($cmdline = "[options]") {
		$myself = $this;
		return function () use ($myself, $cmdline) {
			echo "Usage:\n";
			echo "\t" . $myself->programName() . " $cmdline\n";
			echo "\n";
			echo "Available Options:\n";
			echo $myself->help();
			exit();
		};
	}

	public function parse($options = null) {
		if (is_null($options)) {
			$options = $GLOBALS['argv'];

			// First entry may be 'php'
			$cmd = array_shift($options);

			if (preg_match('/^(.*\/)?php5?(\.exe)?$/', $cmd)) {
				$cmd .= ' ' . array_shift($options);
			}

			$this->programNameParsed = $cmd;
		}

		if (! is_array($options)) {
			throw new Exception('Unable to parse options - they are not an array');
		}

		$unparsed = array();

		while (count($options)) {
			$current = $options[0];

			if ($current == '--') {
				// Designator for the end of arguments
				array_shift($options);  // Shift off "--"
				return array_merge($unparsed, $options);
			}

			if (! strncmp($current, '--', 2)) {
				// Long option
				$arg = substr($current, 2);
				$value = null;
				$found = null;

				if (preg_match('/^([^=]+)=(.*)$/', $arg, $matches)) {
					$arg = $matches[1];
					$value = '';

					if (count($matches) > 2) {
						$value = $matches[2];
					}
				}

				// Match a regular argument
				foreach ($this->parameters as $p) {
					if ($p->matchLongArgument($arg)) {
						$found = $p;
					}
				}

				// Autocomplete if possible
				if (! $found && $this->doAutocomplete) {
					$suggestions = array();

					foreach ($this->parameters as $p) {
						foreach ($p->matchAutocomplete($arg) as $name => $obj) {
							$suggestions[$name] = $obj;
						}
					}

					if (count($suggestions) == 1) {
						$arg = array_keys($suggestions);
						$arg = reset($arg);
						$found = $suggestions[$arg];
					}
				}

				// Wildcard if possible
				if (! $found) {
					foreach ($this->parameters as $p) {
						if ($p->matchWildcard()) {
							$found = $p;
						}
					}
				}

				array_shift($options);

				if (! $found) {
					$reconstructed = '--' . $arg;
					
					if (! is_null($value)) {
						$reconstructed .= '=' . $value;
					}

					$unparsed[] = $reconstructed;
				} else {
					switch ($found->usesArgument()) {
						case OptionParameter::ARGUMENT_NONE:
							// Must not use "=" syntax
							// Treat it as another paramter if found
							$found->handleArgument($arg);

							if (! is_null($value)) {
								array_unshift($options, '=' . $value);
							}

							break;

						case OptionParameter::ARGUMENT_REQUIRED:
							// Can use "=" syntax or next argument
							if (is_null($value)) {
								if (! count($options)) {
									throw new Exception('Value needed for --' . $arg);
								}

								$value = array_shift($options);
							}

							$found->handleArgument($arg, $value);
							break;

						default:  // Optional
							// Must use "=" syntax
							$found->handleArgument($arg, $value);
							break;
					}
				}
			} elseif (! strncmp($current, '-', 1)) {
				// Short option
				$arg = substr($current, 1, 1);
				$rest = substr($current, 2);
				$found = null;

				// Match a regular argument
				foreach ($this->parameters as $p) {
					if ($p->matchShortArgument($arg)) {
						$found = $p;
					}
				}

				// Wildcard if possible
				if (! $found) {
					foreach ($this->parameters as $p) {
						if ($p->matchWildcard()) {
							$found = $p;
						}
					}
				}

				if (! $found) {
					$unparsed[] = '-' . $arg;

					if (strlen($rest)) {
						$options[0] = '-' . $rest;
					} else {
						array_shift($options);
					}
				} else {
					switch ($found->usesArgument()) {
						case OptionParameter::ARGUMENT_NONE:
							// Must not use "=" syntax
							$found->handleArgument($arg);
							array_shift($options);

							if (strlen($rest)) {
								array_unshift($options, '-' . $rest);
							}

							break;

						case OptionParameter::ARGUMENT_REQUIRED:
							// Can use "=" syntax, the rest of the current arg
							// or next argument
							array_shift($options);

							if (strlen($rest)) {
								if (! strncmp($rest, '=', 1)) {
									$rest = substr($rest, 1);
								}
							} else {
								if (! count($options)) {
									throw new Exception('Value needed for -' . $arg);
								}

								$rest = array_shift($options);
							}

							$found->handleArgument($arg, $rest);
							break;

						default:  // Optional
							// Must use "=" syntax
							array_shift($options);

							if (! strncmp($rest, '=', 1)) {
								$found->handleArgument($arg, substr($rest, 1));
							} else {
								$found->handleArgument($arg);

								if (strlen($rest)) {
									array_unshift($options, '-' . $rest);
								}
							}
							break;
					}
				}
			} elseif (! $this->doScanAll) {
				// Stopping at first unknown
				return array_merge($unparsed, $options);
			} else {
				$unparsed[] = array_shift($options);
			}
		}

		return $unparsed;
	}

	public function programName() {
		return $this->programNameParsed;
	}

	public function scanAll($boolean) {
		$this->doScanAll = !! $boolean; // Force $boolean to be a boolean
	}
}
