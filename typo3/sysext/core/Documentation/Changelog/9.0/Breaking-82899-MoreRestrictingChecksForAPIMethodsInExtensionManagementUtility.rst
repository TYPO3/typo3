.. include:: /Includes.rst.txt

========================================================================================
Breaking: #82899 - More restricting checks for API methods in ExtensionManagementUtility
========================================================================================

See :issue:`82899`

Description
===========

The following methods within :php:`ExtensionManagementUtility`, the primary API class for
extensions registering additional components like plugins, modules or extending TCA functionality
now throw Exceptions with invalid calls:

1. :php:`addLLrefForTCAdescr()` requires a non-empty string as first argument
2. :php:`addNavigationComponent()` requires the third argument ($extensionKey)
3. :php:`addService()` requires the second argument to be non-empty, and the fourth argument as array
4. :php:`addPlugin()` requires the third argument ($extensionKey) to be set
5. :php:`addStaticFile()` requires the second and third argument to be non-empty
6. :php:`addTypoScript()` requires the second argument to be either `setup` or `constants`


Impact
======

Calling any of the methods mentioned will trigger a `InvalidArgumentException`.


Affected Installations
======================

Any TYPO3 installation with an extension calling any of the methods above with missing
information.


Migration
=========

Add the required parameters to the API calls in your extension registration files, typically
located within :file:`ext_localconf.php`, :file:`ext_tables.php` or :file:`Configuration/TCA/*` of a extension.

.. index:: PHP-API, PartiallyScanned
