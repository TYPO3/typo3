
.. include:: ../../Includes.txt

====================================================================
Deprecation: #77164 - ErrorpageMessage and AbstractStandaloneMessage
====================================================================

See :issue:`77164`

Description
===========

The two PHP classes `ErrorpageMessage` and `AbstractStandaloneMessage` have been marked as deprecated.


Impact
======

Instantiating one of the PHP classes will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using the two PHP classes directly because of a specialized error handling or exception handling method.


Migration
=========

Use the new Fluid-based ErrorPageController class.

.. index:: PHP-API
