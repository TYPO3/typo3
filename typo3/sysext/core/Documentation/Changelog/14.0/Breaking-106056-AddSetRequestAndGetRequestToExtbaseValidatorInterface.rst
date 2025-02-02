..  include:: /Includes.rst.txt

..  _breaking-106056-1741414380:

===============================================================================
Breaking: #106056 - Add setRequest and getRequest to extbase ValidatorInterface
===============================================================================

See :issue:`106056`

Description
===========

Custom validators implementing
:php:`TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface` must now
implement the methods :php:`setRequest()` and :php:`getRequest()`.


Impact
======

Missing implementation of the methods :php:`setRequest()` and
:php:`getRequest()` will now result in a PHP fatal error.


Affected installations
======================

TYPO3 websites implementing :php:`TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface`.


Migration
=========

The methods :php:`setRequest()` and :php:`getRequest()` must be implemented in
affected validators.

If no direct implementation of
:php:`TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface` is required,
it is recommended to extend :php:`TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator`,
where both methods already are implemented.

..  index:: Backend, NotScanned, ext:extbase
