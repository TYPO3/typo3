
.. include:: ../../Includes.txt

===================================================================================
Breaking: #65778 - MediaWizard functionality is moved to system extension "mediace"
===================================================================================

See :issue:`65778`

Description
===========

The Media Wizard Provider for the "media" Content Element Type has been moved to the same system extension.

Impact
======

Any extensions registering their own Media Wizards need to install the system extension "mediace" and define a dependency
to this extension.


Affected installations
======================

TYPO3 CMS 7 installations using the "MEDIA" cObject or having Content Elements of CType "media" or "multimedia" with
custom media wizard providers.


Migration
=========

Make sure to install the system extension "mediace" and rename the function calls to use the new classes, see
the file :file:`ext_localconf.php` of the extension "mediace" for example usage.


.. index:: PHP-API, ext:mediace, Frontend, Backend
