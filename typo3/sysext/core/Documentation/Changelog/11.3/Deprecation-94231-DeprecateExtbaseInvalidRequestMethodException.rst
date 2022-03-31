.. include:: /Includes.rst.txt

===========================================================
Deprecation: #94231 - Extbase InvalidRequestMethodException
===========================================================

See :issue:`94231`

Description
===========

To further prepare towards PSR-7 Requests in Extbase, the
:php:`TYPO3\CMS\Extbase\Mvc\Request` has to be streamlined.

Therefore, the internal method :php:`setMethod()` has been removed.
This method previously threw the :php:`InvalidRequestMethodException`.
Since this was the only usage and the exception is not used within
TYPO3 / Extbase anymore, the exception is deprecated.

Impact
======

Using :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException`
in custom extension code is discouraged since it will be removed with TYPO3
v12 and is also no longer thrown by TYPO3.

Affected Installations
======================

Extbase based extensions may manually throw or catch
:php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException`.
The extension scanner will find those usages.

Migration
=========

All usages of :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException`
in custom extension code, which is very unlikely, have to be replaced with a
custom exception, if needed at all.

.. index:: PHP-API, FullyScanned, ext:extbase
