.. include:: /Includes.rst.txt

=======================================================================
Deprecation: #80513 - DataHandler: Various methods and method arguments
=======================================================================

See :issue:`80513`

Description
===========

The method :php:`DataHandler->destPathFromUploadFolder()` has been marked as deprecated.

The fourth parameter :php:`$func` of the method :php:`DataHandler->extFileFunctions()` has been deprecated.


Impact
======

Calling the method :php:`DataHandler->destPathFromUploadFolder()` will trigger a deprecation log entry.

Calling the method :php:`DataHandler->extFileFunctions()` with the fourth parameter (usually set to
'deleteAll') will trigger a deprecation log entry.


Affected Installations
======================

Any installation with custom extension logic using the DataHandler and specifically these methods.


Migration
=========

Replace the function call :php:`DataHandler->destPathFromUploadFolder()` by prepend the
constant :php:`PATH_site` before the string to be handed over to the deprecated method.

Remove the fourth parameter of the callee of :php:`DataHandler->extFileFunctions()`.

.. index:: PHP-API, Backend
