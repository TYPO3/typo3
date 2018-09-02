.. include:: ../../Includes.txt

=======================================================================
Deprecation: #85802 - Move FlexFormService from EXT:extbase to EXT:core
=======================================================================

See :issue:`85802`

Description
===========

Move FlexFormService from EXT:extbase to EXT:core.


Impact
======

The PHP class :php:`TYPO3\CMS\Extbase\Service\FlexFormService` has been moved from the system
extension `extbase` to `core`. The PHP class has been renamed to
:php:`TYPO3\CMS\Core\Service\FlexFormService`.


Affected Installations
======================

Any TYPO3 installation where this PHP class is in use within a TYPO3 extension.


Migration
=========

Use the new namespace to reference the :php:`TYPO3\CMS\Core\Service\FlexFormService`.

.. index:: PHP-API, FullyScanned, ext:extbase
