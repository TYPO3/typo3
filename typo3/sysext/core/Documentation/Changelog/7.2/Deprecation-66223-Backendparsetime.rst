
.. include:: ../../Includes.txt

==================================================
Deprecation: #66223 - Backend parseTime deprecated
==================================================

See :issue:`66223`

Description
===========

The option to show the parse time of the rendered script on the bottom of the HTML page has been marked for deprecation
and is not in use anymore.


Impact
======

Debug information is not shown anymore on a backend page if the member var `$parseTimeFlag` is enabled.


Affected Installations
======================

Installations with 3rd-party extensions manually activating this option by e.g. XCLASSing or hooking into the main
document template class.


Migration
=========

Do not set the member var to `TRUE`.


.. index:: PHP-API, Backend
