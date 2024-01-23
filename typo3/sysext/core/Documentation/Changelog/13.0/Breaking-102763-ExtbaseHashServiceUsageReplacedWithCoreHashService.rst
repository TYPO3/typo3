.. include:: /Includes.rst.txt

.. _breaking-102763-1706362598:

============================================================================
Breaking: #102763 - Extbase HashService usage replaced with Core HashService
============================================================================

See :issue:`102763`

Description
===========

All usages of the :php:`@internal` class
:php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService` in TYPO3 have been
removed and replaced by :php:`\TYPO3\CMS\Core\Crypto\HashService`.


Impact
======

Custom extensions expecting :php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService`
as type of the property :php:`'$hashService` of various :php:`@internal` classes
will result in a PHP Fatal error.


Affected installations
======================

TYPO3 installations with custom extensions using one of the following:

- Property :php:`$hashService` of :php:`\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper`
- :php:`@internal` property :php:`$hashService` of class :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`
- Property :php:`$hashService` of :php:`internal` class :php:`\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService`
- Property :php:`$hashService` of :php:`internal` class :php:`\TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration`
- Property :php:`$hashService` of :php:`internal` class :php:`\TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration`
- Property :php:`$hashService` of :php:`internal` class :php:`\TYPO3\CMS\FrontendLogin\Controller\PasswordRecoveryController`
- Property :php:`$hashService` of :php:`internal` class :php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime`
- Property :php:`$hashService` of :php:`internal` class :php:`\TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter`


Migration
=========

Custom extensions must be adapted to use methods of class
:php:`\TYPO3\CMS\Core\Crypto\HashService`.

.. index:: Fluid, Frontend, PHP-API, FullyScanned, ext:extbase
