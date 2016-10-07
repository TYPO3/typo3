
.. include:: ../../Includes.txt

============================================================
Breaking: #72022 - Removed class loading fallback in cObject
============================================================

See :issue:`72022`

Description
===========

The method `ContentObjectRenderer->isClassAvailable()` was used internally to check for a TypoScript property
`plugin.tx_myextension_pi1.includeLibs` that included a PHP file when `class_exists()` failed.

With TYPO3 CMS 7, the spl_autoload mechanism checks for all places within extensions, alternatively composer does this
on build-time. All needed classes are known, making this check and the option obsolete.

The functionality was introduced in TYPO3 4.3 before autoloading was available,
and has now been removed.


Impact
======

The option `.includeLibs` on a plugin TypoScript object has no effect anymore.

.. index:: PHP-API, TypoScript
