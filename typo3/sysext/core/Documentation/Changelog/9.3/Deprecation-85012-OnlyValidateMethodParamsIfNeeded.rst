.. include:: /Includes.rst.txt

=================================================================================
Deprecation: #85012 - GetValidationResults of Argument:class and Arguments::class
=================================================================================

See :issue:`85012`

Description
===========

The following public methods have been marked as deprecated:

* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument::getValidationResults()`
* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Arguments::getValidationResults()`

Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extensions that call any of the methods. The extension scanner will find usages.


Migration
=========

Use the following methods instead:

* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument::validate()`
* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Arguments::validate()`


.. index:: PHP-API, FullyScanned, ext:extbase
