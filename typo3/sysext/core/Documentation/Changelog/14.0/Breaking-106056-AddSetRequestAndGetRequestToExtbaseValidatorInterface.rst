..  include:: /Includes.rst.txt

..  _breaking-106056-1741414380:

===============================================================================
Breaking: #106056 - Add setRequest and getRequest to Extbase ValidatorInterface
===============================================================================

See :issue:`106056`

Description
===========

Custom validators implementing
:php:`\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface` must now
also implement the methods :php:`setRequest()` and :php:`getRequest()`.

Impact
======

Missing implementations of the methods :php:`setRequest()` and
:php:`getRequest()` will now result in a PHP fatal error.

Affected installations
======================

TYPO3 installations with custom extensions implementing
:php-short:`\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface`.

Migration
=========

The methods :php:`setRequest()` and :php:`getRequest()` must be implemented in
affected validators.

If there is no need to directly implement
:php-short:`\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface`, it is
recommended to extend
:php-short:`\TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator`, where
both methods are already implemented.

..  index:: Backend, NotScanned, ext:extbase
