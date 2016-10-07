
.. include:: ../../Includes.txt

=========================================
Deprecation: #76164 - Deprecate RemoveXSS
=========================================

See :issue:`76164`

Description
===========

Due to the wrong approach of RemoveXSS it is not 100% secure and does not keep its
promise. The following methods have been marked as deprecated:

- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS()`
- :php:`\RemoveXSS::process()`
- :php:`\TYPO3\CMS\Form\Domain\Filter\RemoveXssFilter`


Impact
======

Using the mentioned methods will trigger a deprecation log entry.


Affected Installations
======================

Instances that use any of these methods.


Migration
=========

Implement a proper encoding by yourself. Use :php:`htmlspecialchars()` in the
context of HTML or :php:`GeneralUtility::quoteJSvalue()` in the context of JavaScript.

.. index:: PHP-API
