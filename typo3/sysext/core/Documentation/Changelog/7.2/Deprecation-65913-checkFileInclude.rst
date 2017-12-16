
.. include:: ../../Includes.txt

=======================================================
Deprecation: #65913 - Deprecate $TSFE->checkFileInclude
=======================================================

See :issue:`65913`

Description
===========

The public method in the global frontend controller `$TSFE->checkFileInclude()` has been marked as deprecated.


Affected installations
======================

Instances with extensions that make use of the method directly.


Migration
=========

Use the autoloader for classes or `$TSFE->tmpl->getFileName()` if needed.
