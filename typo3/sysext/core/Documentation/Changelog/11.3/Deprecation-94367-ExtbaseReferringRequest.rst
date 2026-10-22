.. include:: /Includes.rst.txt

.. _deprecation-94367:

==============================================
Deprecation: #94367 - Extbase ReferringRequest
==============================================

See :issue:`94367`

Description
===========

To further prepare Extbase towards PSR-7 compatible requests, Extbase class
:php:`TYPO3\CMS\Extbase\Mvc\Web\ReferringRequest` has been deprecated.


Impact
======

Creating an instance of :php-short:`\TYPO3\CMS\Extbase\Mvc\Web\ReferringRequest` a PHP :php:`E_USER_DEPRECATED`
error.


Affected Installations
======================

:php-short:`\TYPO3\CMS\Extbase\Mvc\Web\ReferringRequest` has been mostly Extbase internal and rarely used in
Extbase extensions, probably only in cases where
:php:`ActionController->forwardToReferringRequest()` is overridden.
The extension scanner will find usages with a strong match.

Migration
=========

Extbase internally, :php-short:`\TYPO3\CMS\Extbase\Mvc\Web\ReferringRequest` has only been used to
immediately create a :php-short:`\TYPO3\CMS\Extbase\Http\ForwardResponse` from it. Consuming extensions
should follow his approach and create a :php-short:`\TYPO3\CMS\Extbase\Http\ForwardResponse` directly.

.. index:: PHP-API, FullyScanned, ext:extbase
