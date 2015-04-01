==================================================
Deprecation - #66223: Backend parseTime deprecated
==================================================

Description
===========

The option to show the parse time of the rendered script on the bottom of the HTML page has been marked for deprecation
and is not in use anymore.


Impact
======

Debug information is not output anymore on a backend page if the flag is enabled.


Affected Installations
======================

Installations with 3rd-party extensions manually activating this option by e.g. XCLASSing or hooking into the main
document template class.