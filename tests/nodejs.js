var OP = require('../nodejs/OptionParser');
var fs = require('fs');
var scenariosDir = 'scenarios/';

var pending = 0;
var failures = 0;

fs.readdir(scenariosDir, function (err, files) {
	if (err) {
		throw err;
	}

	files.forEach(function (filename) {
		if (filename.match(/\.json$/)) {
			runScenario(scenariosDir + filename);
		}
	});
});

function runScenario(filename) {
	pending ++;
	fs.readFile(filename, function (err, data) {
		if (err) {
			throw err;
		}

		var json = JSON.parse(data);

		if (typeof json != "object") {
			throw new Error('Unable to load JSON: ' + filename);
		}

		json.tests.forEach(function (test) {
			var parser = makeParser(json);
			var parseAsString = JSON.stringify(test.parse);
			var unparsed = parser.parse(test.parse);
			var getopt = parser.getopt();

			assertSame(test.getopt, getopt, "Getopt difference running scenario " + filename + " (" + parseAsString + ")");
			assertSame(test.unparsed, unparsed, "Unparsed difference runnning scenario " + filename + " (" + parseAsString + ")");
		});

		var parser = makeParser(json);
		var helpText = parser.help(16, 2, 78).replace(/[ \t\r\n]*$/, '').split("\n");
		assertSame(json.help, helpText, "Help text difference for scenario " + filename);
			
		summary();
	});
}

function summary() {
	pending --;

	if (pending) {
		return;
	}

	if (failures) {
		console.log("Failures detected");
	} else {
		console.log("All tests in all scenarios passed!");
	}
}

function makeParser(json) {
	var parser = new OP.OptionParser();
	parser.autocomplete(json.autocomplete);
	parser.scanAll(json.scanAll);
	
	json.options.forEach(function (opt) {
		var param = parser.addOption(opt.shortOptions, opt.longOptions, opt.help, opt.name);

		if (opt.argumentName) {
			param.argument(opt.argumentName, opt.argumentRequired);
		}
	});

	return parser;
}

function assertSame(expected, actual, message) {
	expected = sortObject(expected);
	actual = sortObject(actual);

	if (JSON.stringify(actual) != JSON.stringify(expected)) {
		console.log(message);
		console.log("Expected:");
		console.log(expected);
		console.log("Actual:");
		console.log(actual);
		console.log("");
		failures ++;
	}
}

function sortObject(input) {
	var output = {};
	var keys = [];

	for (var i in input) {
		keys.push(i);
	}

	keys.sort();

	keys.forEach(function (item) {
		output[item] = input[item];
	});

	return output;
}
