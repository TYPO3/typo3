
.. include:: /Includes.rst.txt

=============================================
Breaking: #67212 - Discard TYPO3 class loader
=============================================

See :issue:`67212`

Description
===========

The former TYPO3 class loader has been removed in favor of the composer class loader.


Impact
======

ext_autoload.php files are **not** evaluated any more. Instead all class files are registered
automatically during extension installation and written into a class map file. This class map file is
not changed during regular requests, but only if the extension list changes (by using the Extension Manager).

These class information files are located in the :file:`typo3temp/autoload/` directory and will also be automatically
created if they do not exist.

Non-namespaced classes with `Tx_` naming convention like `Tx_Extension_ClassName` are only resolved through
the aforementioned class map, but not dynamically. This means that extension authors need to re-generate the class map
files when introducing new classes. Thus it is highly recommended to use a Classes folder with PSR-4 standard class
files in there.

When installing TYPO3 with composer, it also means that all extensions need to bring their own :file:`composer.json`
file with class loading information or the class loading information of all extensions need to be specified in the root
:file:`composer.json` for class loading to work properly.


Affected Installations
======================

All installations are affected.


Migration
=========

No migration is needed during upgrade if TYPO3 is installed in the classic way.
If TYPO3 is installed in a distribution via composer, missing class loading information need to be provided in root
composer.json for all extensions which do not bring their own composer.json manifest.

.. code-block:: json

    {
        "autoload": {
            "psr-4": {
                "GeorgRinger\\News\\": "typo3conf/ext/news/Classes/",
                "MyAwesomeNamespace\\IncrediExt\\": "typo3conf/ext/incredible_extension/Resources/PHP/Libraries/lib/"
            }
        }
    }


.. index:: PHP-API
