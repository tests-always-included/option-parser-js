<?php

require_once(__DIR__ . '/../php/OptionParser.class.php');

$scenarios = glob('scenarios/*.json');
$failCount = 0;

foreach ($scenarios as $scenarioFile) {
	$failCount += runScenario($scenarioFile);
}

if ($failCount) {
	echo "Failures detected\n";
} else {
	echo "All tests in all scenarios passed!\n";
}

function runScenario($scenarioFile) {
	$failures = 0;
	$json = json_decode(file_get_contents($scenarioFile));

	if (is_null($json)) {
		echo "Unable to read or parse $scenarioFile\n\n";
		return 1;
	}

	foreach ($json->tests as $test) {
		$parser = makeParser($json);
		$parseAsString = json_encode($test->parse);
		$unparsed = $parser->parse($test->parse);
		$getopt = $parser->getopt();

		$failures += assertSame($test->getopt, $getopt, "Getopt difference running scenario $scenarioFile ($parseAsString)");
		$failures += assertSame($test->unparsed, $unparsed, "Unparsed difference running scenario $scenarioFile ($parseAsString)");
	}

	$parser = makeParser($json);
	$helpText = $parser->help(16, 2, 78);
	$helpText = rtrim($helpText);
	$helpText = explode("\n", $helpText);
	$failures += assertSame($json->help, $helpText, "Help text difference for scenario $scenarioFile");

	return $failures;
}

function makeParser($json) {
	$parser = new OptionParser();
	$parser->autocomplete($json->autocomplete);
	$parser->scanAll($json->scanAll);
	
	foreach ($json->options as $opt) {
		$param = $parser->addOption($opt->shortOptions, $opt->longOptions, $opt->help, $opt->name);
		if (! empty($opt->argumentName)) {
			$param->argument($opt->argumentName, $opt->argumentRequired);
		}
	}

	return $parser;
}

function assertSame($expected, $actual, $message) {
	if (is_array($actual) && is_object($expected)) {
		$expected = (array) $expected;
		ksort($expected);
		ksort($actual);
	}

	if ($actual !== $expected) {
		echo "$message\n";
		echo "Expected:\n";
		var_export($expected);
		echo "\n";
		echo "Actual:\n";
		var_export($actual);
		echo "\n\n";
		return 1;
	}

	return 0;
}
