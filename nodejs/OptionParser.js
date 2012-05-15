'use strict';

var OptionParameter = require('./OptionParameter');

var undefined = (function (u) { return u; })();

var OptionParser = function () {
	this.doAutocomplete = false;
	this.doScanAll = true;
	this.parameters = [];
	this.programNameParsed = null;
};

OptionParser.prototype.addOption = function (shortName, longName, helpMessage, referenceName) {
	var op = new OptionParameter.OptionParameter(shortName, longName, helpMessage);
	this.parameters.push(op);

	if (referenceName) {
		this[referenceName] = op;
	}

	return op;
};

OptionParser.prototype.autocomplete = function (bool) {
	this.doAutocomplete = !! bool;  // Force bool to be a boolean
	return this;
};

OptionParser.prototype.get = function (name) {
	if (! name || ! this[name]) {
		throw new Error('No parameter named ' . name);
	}

	return this[name];
};

OptionParser.prototype.getopt = function () {
	var out = {};

	this.parameters.forEach(function (p) {
		var getopt = p.getopt();

		for (var i in getopt) {
			out[i] = getopt[i];
		}
	});

	return out;
};

OptionParser.prototype.getValue = function (name) {
	return this.get(name).value();
};

OptionParser.prototype.help = function () {
	var out = '';

	this.parameters.forEach(function (p) {
		out += p.help();
	});

	return out;
};

OptionParser.prototype.helpAction = function (cmdline) {
	if (! cmdline) {
		cmdline = "[options]";
	}

	var myself = this;
	return function () {
		console.log("Usage:");
		console.log("\t" + myself.programName() + " " + cmdline);
		console.log("");
		console.log("Available Options:");
		console.log(myself.help());
		process.exit(0);
	};
};

OptionParser.prototype.parse = function (options) {
	if (arguments.length < 1) {
		options = process.argv.slice(0);

		// First option is "node", second is program name
		this.programNameParsed = options.shift() + ' ' + options.shift();
	}

	if (! Array.isArray(options)) {
		throw new Error('Unable to parse options - they are not an array');
	}

	var unparsed = [];

	while (options.length) {
		var current = options[0];
		var arg = null;
		var found = null;

		if (current == '--') {
			// Designator for the end of arguments
			options.shift();  // Shift off "--"
			return unparsed.concat(options);
		}

		if (current.substr(0, 2) == '--') {
			// Long option
			arg = current.substr(2);
			var value = null;
			var matches = arg.match(/^([^=]+)=(.*)$/);

			if (matches) {
				arg = matches[1];
				value = matches[2];
			}

			// Match a regular argument
			this.parameters.forEach(function (p) {
				if (p.matchLongArgument(arg)) {
					found = p;
				}
			});

			// Autocomplete if possible
			if (! found && this.doAutocomplete) {
				var suggestionCount = 0;
				var suggestionName = null;
				var suggestionObject = null;

				this.parameters.forEach(function (p) {
					var hits = p.matchAutocomplete(arg);

					for (var i in hits) {
						suggestionCount ++;
						suggestionName = i;
						suggestionObject = hits[i];
					}
				});

				if (suggestionCount == 1) {
					arg = suggestionName;
					found = suggestionObject;
				}
			}

			// Wildcard if possible
			if (! found) {
				this.parameters.forEach(function (p) {
					if (p.matchWildcard()) {
						found = p;
					}
				});
			}

			options.shift();

			if (! found) {
				var reconstructed = '--' + arg;

				if (value !== null) {
					reconstructed += '=' + value;
				}

				unparsed.push(reconstructed);
			} else {
				switch (found.usesArgument()) {
					case 'none':
						// Must not use '=' syntax
						// Treat it as another parameter if found
						found.handleArgument(arg, null);

						if (value !== null) {
							options.unshift('=' + value);
						}

						break;

					case 'required':
						// Can use '=' syntax or next argument
						if (value === null) {
							if (! options.length) {
								throw new Error('Value needed for --' + arg);
							}

							value = options.shift();
						}

						found.handleArgument(arg, value);
						break;

					default:  // optional
						// Must use '=' syntax if a value is to be specified
						found.handleArgument(arg, value);
						break;
				}
			}
		} else if (current.charAt(0) == '-') {
			// Short option
			arg = current.charAt(1);
			var rest = current.substr(2);

			// Match a regular argument
			this.parameters.forEach(function (p) {
				if (p.matchShortArgument(arg)) {
					found = p;
				}
			});

			// Wildcard if possible
			if (! found) {
				this.parameters.forEach(function (p) {
					if (p.matchWildcard()) {
						found = p;
					}
				});
			}

			if (! found) {
				unparsed.push('-' + arg);
				options.shift();

				if (rest.length) {
					options.unshift('-' + rest);
				}
			} else {
				switch (found.usesArgument()) {
					case 'none':
						// Must not use '=' syntax
						found.handleArgument(arg, null);
						options.shift();

						if (rest.length) {
							options.unshift('-' + rest);
						}

						break;

					case 'required':
						// Can use '=' syntax, the rest of the current arg
						// or next argument
						options.shift();

						if (rest.length) {
							if (rest.charAt(0) == '=') {
								rest = rest.substr(1);
							}
						} else {
							if (! options.length) {
								throw new Error('Value needed for -' . arg);
							}

							rest = options.shift();
						}

						found.handleArgument(arg, rest);
						break;

					case 'optional':
						// Must use '=' syntax
						options.shift();

						if (rest.charAt(0) == '=') {
							found.handleArgument(arg, rest.substr(1));
						} else {
							found.handleArgument(arg, null);

							if (rest.length) {
								options.unshift('-' + rest);
							}
						}
						break;

					default:
						throw new Error('Invalid OptionParameter argument type: ' + found.usesArgument());
				}
			}
		} else if (! this.doScanAll) {
			// Stopping at first unknown
			return unparsed.concat(options);
		} else {
			unparsed.push(options.shift());
		}
	}

	return unparsed;
};

OptionParser.prototype.programName = function () {
	return this.programNameParsed;
};

OptionParser.prototype.scanAll = function (bool) {
	this.doScanAll = !! bool;  // Force bool to be a boolean
};

exports.OptionParser = OptionParser;
