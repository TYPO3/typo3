
.. include:: /Includes.rst.txt

===========================================================
Feature: #60064 - Logging Framework Introspection Processor
===========================================================

See :issue:`60064`

Description
===========

The introspection processor of the logging framework has been extended to log the full PHP backtrace and not only the last
element of a backtrace.

Two options were added to enable this feature:

- :code:`appendFullBackTrace`, boolean, not mandatory` Add full backtrace to the log

- :code:`shiftBackTraceLevel`, integer, default 0, not mandatory` Removes the given number of entries from the top of the backtrace stack.

Impact
======

The introspection processor behaves as before as long as the feature is not explicitly configured.


.. index:: PHP-API, Backend
