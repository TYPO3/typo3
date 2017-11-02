.. include:: ../../Includes.txt

========================================================================================
Breaking: #82899 - More restricting checks for API methods in ExtensionManagementUtility
========================================================================================

See :issue:`82899`

Description
===========

The following methods within :php:``ExtensionManagementUtility``, the primary API class for
extensions registering additional components like plugins, modules or extending TCA functionality
now throw Exceptions with invalid calls:

1. ``addLLrefForTCAdescr()`` requires a non-empty string as first argument
2. ``addNavigationComponent()`` requires the third argument ($extensionKey)
3. ``addService()`` requires the second argument to be non-empty, and the fourth argument as array
4. ``addPlugin()`` requires the third argument ($extensionKey) to be set
5. ``addStaticFile()`` requires the second a third argument to be non-empty
6. ``addTypoScript()`` requires the second argument to be either `setup` or `constants`


Impact
======

Calling any of the methods mentioned will trigger a "InvalidArgumentException".


Affected Installations
======================

Any TYPO3 installation with an extension calling any of the methods above with missing
information.


Migration
=========

Add the required parameters to the API calls in your extension registration files, typically
located within ``ext_localconf.php``, ``ext_tables.php`` or ``Configuration/TCA/*`` of a extension.

.. index:: PHP-API, NotScanned