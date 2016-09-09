
.. include:: ../../Includes.txt

====================================================================
Breaking: #70503 - EXT:form - Remove breakOnError option from wizard
====================================================================

See :issue:`70503`

Description
===========

The validation option `breakOnError` is not supported anymore. The option
has been removed completely.


Impact
======

The validation process cannot be interrupted anymore, i.e. the whole form
will be validated and all error messages will be shown.


Affected Installations
======================

Any installation that implements the `breakOnError` functionality.


Migration
=========

The function has been removed without any substitution. Therefore there is
no migration possible. The attribute can be manually removed from the form
configuration. The wizard ignores the existence of `breakOnError`.
Since TYPO3 7.5 it is possible to utilize HTML5 attributes to validate
form elements on the fly. This could be used to emulate the behaviour.
