#!/usr/bin/php
<?php

require_once(__DIR__ . '/../php/OptionParser.class.php');

$parser = new OptionParser();

$requiredValue = null;

/* Method 1 for handling options - use a callback */
$parser->addOption('h', 'help', 'Show a help message')
	->action($parser->helpAction());  // We provide a quick help action

$parser->addOption('r', 'required', 'Specify a required option')
	->argument('OPTION')
	->action(function ($v) use (&$requiredValue) {
		// Can handle values in closures and anything else that's callable
		// by call_user_func()
		$requiredValue = $v;
		echo "Required value:  $v\n";
	});

/* Method 2 for handling options - save a reference to the object */
$optional = $parser->addOption('o', 'optional', 'Add an optional value')
	->argument('VALUE', false);  // Parse these by hand later

/* Method 3 for handling options - name the option */
$parser->addOption('f', 'flag', 'Turn on some flag', 'flag');

$parser->addOption('d', 'debug', null, 'debug');  // Hidden option

try {
	$unparsed = $parser->parse();

	/* Second part of "Method 2" */
	foreach ($optional->values() as $value) {
		echo "Optional value:  " . json_encode($value) . "\n";
	}

	/* Second part of "Method 3" */
	echo "Times the --flag was specified:  " . $parser->flag->count() . "\n";
	echo "Times the hidden --debug option was used:  " . $parser->debug->count() . "\n";
	echo "\n";

	/* Add some diagnostic information */
	echo "getopt:\n";
	echo json_encode($parser->getopt()) . "\n";
	echo "\n";
	echo "Unparsed:\n";
	echo json_encode($unparsed) . "\n";
} catch (Exception $e) {
	echo "Exception caught:\n";
	echo $e->getMessage() . "\n\n";
}

