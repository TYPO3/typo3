
.. include:: ../../Includes.txt

======================================================================
Deprecation: #76345 - Path prefixes in callUserFunction and getUserObj
======================================================================

See :issue:`76345`

Description
===========

The two methods :php:`GeneralUtility::callUserFunc()` and :php:`GeneralUtility::getUserObj()` allow the first parameter to
contain a file reference to the function/class to be called if prefixed with a colon.

An example would be :php:`EXT:myext/Classes/MyClass.php:Benni\Myext\MyClass` for including the class.

Having the reference to the actual file is not needed since the composer autoloading mechanism takes care
of loading everything properly already since TYPO3 6.2.9.


Impact
======

Calling one of the methods above with a file reference prepended to the class name / function name will
trigger a deprecation log entry.


Affected Installations
======================

Any installation with a hook that is registered with the file prefix functionality.


Migration
=========

Remove the file prefix when registering a hook and make use of the common autoloading functionality of
composer or via the fallback autoloader by TYPO3 to achieve the same functionality automatically.

.. index:: Frontend, Backend, PHP-API
