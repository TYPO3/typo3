=======================================================
Deprecation: #65913 - Deprecate $TSFE->checkFileInclude
=======================================================

Description
===========

The public method in the global frontend controller ``$TSFE->checkFileInclude()`` has been marked as deprecated.


Affected installations
======================

Instances with extensions that make use of the method directly.


Migration
=========

Use the autoloader for classes or ``$TSFE->tmpl->getFileName()`` if needed.
