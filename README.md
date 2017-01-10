`OptionParser` - JavaScript Version
===================================

`OptionParser` is a library to help you parse command-line options, similar to how `getopt` works.  An effort is made to make it POSIX compliant and easy for people to use.  Features of many other implementations have been integrated in order to provide significant flexibility and to make it easier to use.

There is a sample parser in the [examples] directory.

[![npm version][npm-badge]][npm-link]
[![Build Status][travis-badge]][travis-link]
[![Dependencies][dependencies-badge]][dependencies-link]
[![Dev Dependencies][devdependencies-badge]][devdependencies-link]


Features
--------

* -x (short options)
* -xxxyxxz (combined and repeating short options)
* --long (long option)
* -x=Value, --long=Value (optional and required values)
* -xValue --long Value (special format for required values)
* -- (signify the end of options)
* Nearly automatic help generation
* Can stop on the first unparsed option
* Can autocomplete long options
* Returns unparsed options
* Flexible option handling


How to use
----------

First, create the parser object:

    OptionParser = require('option-parser');
    parser = new OptionParser();

Next, you add some options.  I'll jump right in and add the standard "help" option, triggered with either "-h" or "--help".

    parser.addOption('h', 'help', 'Display this help message')
        .action(parser.helpAction());

Finally, parse the command line options.

    parser.parse();


How To Use Parameters
---------------------

The whole point of the library is to make it really easy to handle the parsing
of options to your program.  Let's run through a few examples here:


### Toggle a Flag With A Callback

Let's have this option only work with the short option "-f".

    flagWasSet = false;

    parser.addOption('f', null, 'Toggle a flag')
        .action(function () {
            flagWasSet = true;
        });


### Pass a Required Value

Many options need a specific value, such as an input file to process.  Here is another option that is specified by "-i" or "--input" that requires a filename of the input file.  It uses a callback, just like the above example.

    inputFile = '/dev/stdin';

    parser.addOption('i', 'input', 'Specify an input file')
        .argument('FILE')  // You can name the argument anything you like
        .action(function (value) {
            inputFile = value;
        });


### Optional Value and Parameter Object

Prefer to not use a lot of callbacks?  One alternative would be to use the returned object from setting up the option on the parser.  Here, we add a debug option that lets you set the debug level, but can default to 5 if you don't set one explicitly.

    debugLevel = 0;

    debugOption = parser.addOption(null, 'debug',
         'Sets the debug level; if set, default is 5')
        .argument('Level', false);  // See note below

    // Don't forget to set up the other options here

    parser.parse();
    
    // Now use the debugOption object to set the debug value
    if (debugOption->count()) {
        debugLevel = debugOption->value() || 5;  // Default to 5
    }

The first parameter to `OptionParameter.argument()` is the name of the parameter, as seen in the generated help message.  It doesn't affect the execution of the parser in any other way.  The second parameter, `false`, makes the argument optional.

`debugOption.count()` returns the number of times the argument was specified.  `debugOption.value()` returns the last value passed to the parameter.  For detailed information, check out the documentation for [OptionParameter].


### Named Parameters

Keeping references to the objects can be tedious.  Here is the above example
altered to name the parameter and then use the named parameter.  I'm naming the
parameter "dddd" to help contrast against the previous code.

    debugLevel = 0;

    parser.addOption(null, 'debug',
         'Sets the debug level, default is 5', 'dddd')
        .argument('Level', false);

    // Don't forget to set up the other options here

    parser.parse();
    
    if (parser.dddd.count()) {
        debugLevel = parser.dddd.value();
    }


### Getopt

Lastly, PHP has a unique format for handling command-line arguments using
the built-in function `getopt()`.  After setting up options and calling
`parser.parse()`, you can get back an Object that mimics `getopt()` return
value.  It is ported over from the PHP version of this library to assist with testing.

    // Set up options and then call parse()
    parser.parse();

    // Get back an Object of options like PHP's getopt()
    options = parser.getopt();


Unparsed Options
----------------

If you plan on making a program that takes a list of files or needs to work on the options that were passed to the program but were not parsed by `OptionParser`, that's really simple:

    unparsed = parser.parse();

This will contain an array of options, split out into individual options.  If you passed "-abcd" to your program and it handled "-a", "-b", and "-c", then `unparsed` would be an array that only contains "-d".


More Reading
------------

You can read the documentation for the individual classes to understand more about what they do and how they work.

* [OptionParameter]
* [OptionParser]

Reference implementations are available in the [examples] directory in the repository.


Development
-----------

I would happily accept patches and suggestions.

Tests are *always* included.  To get them running on your system, just make sure the submodules are checked out for the repository.  If you don't have a `test/` folder then run this command:

    git submodule update --init

From there you can run the tests.  Start in the root of this repository and run this command.

    test/run-tests

(Using `npm test` also works well.)


[dependencies-badge]: https://img.shields.io/david/tests-always-included/option-parser-js.svg
[dependencies-link]: https://david-dm.org/tests-always-included/option-parser-js
[devdependencies-badge]: https://img.shields.io/david/dev/tests-always-included/option-parser-js.svg
[devdependencies-link]: https://david-dm.org/tests-always-included/option-parser-js#info=devDependencies
[examples]: examples/
[LICENSE]: LICENSE.md
[npm-badge]: https://img.shields.io/npm/v/option-parser.svg
[npm-link]: https://npmjs.org/package/option-parser
[OptionParameter]: option-parameter.md
[OptionParser]: option-parser.md
[travis-badge]: https://img.shields.io/travis/tests-always-included/option-parser-js/master.svg
[travis-link]: http://travis-ci.org/tests-always-included/option-parser-js
