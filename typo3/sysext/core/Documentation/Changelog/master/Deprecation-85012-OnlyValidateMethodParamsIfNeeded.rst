.. include:: ../../Includes.txt

===========================================================
Deprecation: #85012 - Only validate method params if needed
===========================================================

See :issue:`85012`

Description
===========

The following public methods have been marked as deprecated:

* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument::getValidationResults()`
* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Arguments::getValidationResults()`

Impact
======

Calling the method triggers a deprecation log entry.


Affected Installations
======================

Extensions that call that method. The extension scanner will find usages.


Migration
=========

Use the following methods instead:

* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument::validate()`
* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Arguments::validate()`


.. index:: PHP-API, FullyScanned, ext:extbase
