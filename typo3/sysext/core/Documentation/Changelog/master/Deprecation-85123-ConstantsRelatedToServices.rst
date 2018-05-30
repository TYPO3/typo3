.. include:: ../../Includes.txt

===================================================
Deprecation: #85123 - Constants related to Services
===================================================

See :issue:`85123`

Description
===========

The following constants have been marked as deprecated, and will be removed in TYPO3 v10.0.

- T3_ERR_SV_GENERAL
- T3_ERR_SV_NOT_AVAIL
- T3_ERR_SV_WRONG_SUBTYPE
- T3_ERR_SV_NO_INPUT
- T3_ERR_SV_FILE_NOT_FOUND
- T3_ERR_SV_FILE_READ
- T3_ERR_SV_FILE_WRITE
- T3_ERR_SV_PROG_NOT_FOUND
- T3_ERR_SV_PROG_FAILED

The according constants have been moved to class constants of :php:`TYPO3\CMS\Core\Service\AbstractService`.


Impact
======

These constants will not trigger a deprecation warning, however they will result in a fatal error in TYPO3 v10.0.


Affected Installations
======================

TYPO3 Installations with extensions using these constants or having custom services using these constants.


Migration
=========

Use the class constants provided within :php:`TYPO3\CMS\Core\Service\AbstractService`.

.. index:: PHP-API, FullyScanned