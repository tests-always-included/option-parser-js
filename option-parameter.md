OptionParameter
===============

An instance of the OptionParameter class represents a single option.  It knows
about all of the ways that option can get specified, if it takes a value, and
what values were passed in from the command-line.

Constructor($short, $long, $message = null)
-------------------------------------------

Create a new instance of the class.  $short and $long indicate what options are
allowed.  Multiple short and long options can be specified by passing an array
of allowed options.  A wildcard short option of '*' or '-' will mean that this
option should match any short or long option that didn't match anything else.

$short can be an array of strings or just a single string.  Each string should
be exactly 1 character long.

$long can be an array of strings or just a single string.

$message is the help message to be displayed for this option.

action($callback)
-----------------

Set an action to be performed when you specify this parameter.  In PHP,
$callback can be a closure, function name, array containing a class name and a
method, or anything else that's acceptable to call_user_func().  In JavaScript,
this can only be a closure.

If an invalid $callback is used, an Exception is thrown.

argument($name, $required = true)
---------------------------------

Specify that this parameter takes a value from the command line.

$name is a string for the help message.  It isn't used anywhere else.

$required is a boolean and will default to true if not passed.


count()
-------

Returns the number of times this parameter was used on the command line.


handleArgument($option, $value = null)
--------------------------------------
Performs validation with the optionally specified validation callback.  If that
fails, will throw an Exception.  Once it passes the validation step, an action
callback may be executed if one was assigned via action().  Lastly, this adds
the value to the getopt version of the arguments.

This is intended to be only used by OptionParser.

getopt()
--------

Returns an array to be used by OptionParser->getopt().

getWidth()
----------

Returns the terminal width, employing whatever tricks that might work for you.
Defaults to 80 if it can't find an actual terminal's width.

help($pad = 16, $gutter = 2, $width = null)
-------------------------------------------

Returns a string describing this option and how to invoke it.

$pad is the amount of space on the left for indenting the help option.  It
defaults to 16 spaces.

$gutter is the minimum amount of space required at the end of the short and
long options and before the beginning of the help text.  If there isn't this
much space, the options will be put on a separate line.

$width is the size of the terminal.  The default is null, which means the
option should call getWidth() and try to figure out the terminal width itself.

matchAutocomplete($arg)
-----------------------

Returns an array of options that might match if the user just didn't finish the
entire long option.  for instance, the $arg of "lo" would match "--long".

This is only intended to be called from OptionParser.

matchLongArgument($arg)
-----------------------

Returns true if the argument exactly matches a long argument for this
parameter.

matchShortArgument($arg)
------------------------

Returns true if the argument exactly matches a short argument for this
parameter.


matchWildcard()
---------------

Returns true if this parameter is considered a "wildcard" parameter.  This
means that a short option is either '*' or '-'.

usesArgument()
--------------

Returns whether or not the parameter takes an argument.  Return value is
language-specific.

This is intended to only be called from OptionParser.

validateCallback($callback)
---------------------------

Returns true if the callback is valid.  In PHP, this checks to see if it
is_callable().  For JavaScript, we check to make typeof $callback is
"function".

Throws an Exception if an invalid callback is specified.

validation($callback)
---------------------

Set a validation function to be performed when you specify this parameter.  In
PHP, $callback can be a closure, function name, array containing a class name
and a method, or anything else that's acceptable to call_user_func().  In
JavaScript, this can only be a closure.

The validation function will take a single parameter, which is the value
specified for the parameter.  If the value is valid, null should be returned.
Otherwise, return an informative message as a string, which is set as the
Exception's message.

If an invalid $callback is used, an Exception is thrown.

value()
-------

For a parameter that takes values, returns the last value specified.  Otherwise
return the number of times this parameter was specified.  Similar to values().

values()
--------

For a parameter that takes values, returns an array that contains all values
ever set on the command line.  Otherwise returns the number of times the
parameter was specified.

wrap($str, $pad, $width)
------------------------

Rewraps $str to fit within $width.  When newlines are added, indents the next
line with $pad spaces.
