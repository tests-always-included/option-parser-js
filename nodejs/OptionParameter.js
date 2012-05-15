'use strict';

var undefined = (function (u) { return u; })();

var OptionParameter = function (shortOptions, longOptions, message) {
	this.actionClosure = null;
	this.argumentName = null;
	this.argumentRequired = 'none';
	this.getoptFormat = {};
	this.optionHelp = null;
	this.optionLong = [];
	this.optionShort = [];
	this.validationClosure = null;
	this.valuesParsed = [];

	if (shortOptions) {
		if (Array.isArray(shortOptions)) {
			this.optionShort = shortOptions;
		} else {
			this.optionShort = [ shortOptions ];
		}
	}

	if (longOptions) {
		if (Array.isArray(longOptions)) {
			this.optionLong = longOptions;
		} else {
			this.optionLong = [ longOptions ];
		}
	}

	if (message) {
		this.optionHelp = message;
	}
};

OptionParameter.prototype.action = function (runThis) {
	this.validateCallback(runThis);
	this.actionClosure = runThis;
	return this;
};

OptionParameter.prototype.argument = function (name, required) {
	this.argumentName = name;

	if (required === undefined || required) {
		this.argumentRequired = 'required';
	} else {
		this.argumentRequired = 'optional';
	}

	return this;
};

OptionParameter.prototype.count = function () {
	return this.valuesParsed.length;
};

OptionParameter.prototype.handleArgument = function (option, value) {
	if (value !== null && this.validationClosure) {
		var result = this.validationClosure($value);

		if (result !== null) {
			var exception = new Error(result);
			exception.argument = this;
			throw exception;
		}
	}

	if (this.actionClosure) {
		this.actionClosure(value);
	}

	this.valuesParsed.push(value);
	var getoptValue = false;

	if (this.argumentRequired != 'none' && value !== null) {
		getoptValue = value;
	}

	if (this.getoptFormat[option] === undefined) {
		this.getoptFormat[option] = getoptValue;
	} else {
		if (! Array.isArray(this.getoptFormat[option])) {
			this.getoptFormat[option] = [ this.getoptFormat[option] ];
		}

		this.getoptFormat[option].push(getoptValue);
	}

	return this;
};

OptionParameter.prototype.getopt = function () {
	return this.getoptFormat;
};

OptionParameter.prototype.getWidth = function () {
	if (process.stdout.getWindowSize) {
		return + (process.stdout.getWindowSize()[0]);
	}

	return 80;
};

OptionParameter.prototype.help = function (pad, gutter, width) {
	var doDefault = function (input, defaultValue) {
		if (input === undefined || +input < 0) {
			return defaultValue;
		}

		return input;
	};

	pad = doDefault(pad, 16);
	gutter = doDefault(gutter, 2);
	width = doDefault(width, this.getWidth() - gutter);
	var options = [];
	var buffer = '';

	if (this.optionHelp === null) {
		return '';
	}

	this.optionShort.forEach(function (item) {
		options.push('-' + item);
	});
	this.optionLong.forEach(function (item) {
		options.push('--' + item);
	});

	buffer = options.join(', ');

	if (this.argumentRequired == 'required') {
		buffer += ' ' + this.argumentName;
	} else if (this.argumentRequired == 'optional') {
		buffer += '[=' + this.argumentName + ']';
	}

	var help = this.wrap(Array(pad + 1).join(' ') + this.optionHelp, pad, width);

	if (buffer.length > pad - gutter) {
		help = this.wrap(buffer, 0, width) + help;
	} else {
		help = buffer + help.substr(buffer.length);
	}

	return help;
};

OptionParameter.prototype.matchAutocomplete = function (arg) {
	var hits = {};

	this.optionLong.forEach(function (o) {
		if (o.substr(0, arg.length) == arg) {
			hits[o] = this;
		}
	});

	return hits;
};

OptionParameter.prototype.matchLongArgument = function (arg) {
	return this.optionLong.some(function (item) {
		return item == arg;
	});
};

OptionParameter.prototype.matchShortArgument = function (arg) {
	return this.optionShort.some(function (item) {
		return item == arg;
	});
};

OptionParameter.prototype.matchWildcard = function () {
	return this.optionShort.some(function (item) {
		return item == '*' || item == '-';
	});
};

OptionParameter.prototype.usesArgument = function () {
	return this.argumentRequired;
};

OptionParameter.prototype.validateCallback = function (runThis) {
	if (typeof runThis == 'function') {
		return;
	}

	throw new Error('Invalid closure specified');
};

OptionParameter.prototype.validation = function (runThis) {
	this.validateCallback(runThis);
	this.validationClosure = runthis;
	return this;
};

OptionParameter.prototype.value = function () {
	var ret = this.values();

	if (Array.isArray(ret)) {
		ret = ret.pop();
	}

	return ret;
};

OptionParameter.prototype.values = function () {
	if (this.argumentName === null) {
		return this.count();
	}

	return this.valuesParsed;
};

OptionParameter.prototype.wrap = function (str, pad, width) {
	var lines = [];
	str = str.replace(/[ \t\r\n]*$/, '');
	var spaces = Array(pad + 1).join(' ');
	var newlineMatch;

	while (str.length) {
		newlineMatch = str.match(/\r?\n|\r/);

		if (newlineMatch && newlineMatch.index <= width) {
			lines.push(str.substr(0, newlineMatch.index - 1));
			str = str.substr(newlineMatch.index + newlineMatch[0].length);
		} else {
			var line = str.substr(0, width);
			var spacePos = line.lastIndexOf(' ');

			if (spacePos > width * 0.8) {
				line = line.substr(0, spacePos + 1);
			}

			str = str.substr(line.length);
			lines.push(line.replace(/[ \t\r\n]*$/, ''));
		}

		if (str.length) {
			str = spaces + str;
		}
	}

	return lines.join("\n") + "\n";
};

exports.OptionParameter = OptionParameter;
