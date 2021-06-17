.. include:: ../../Includes.txt

==============================================
Deprecation: #94367 - extbase ReferringRequest
==============================================

See :issue:`94367`

Description
===========

To further prepare extbase towards PSR-7 compatible requests, extbase class
:php:`TYPO3\CMS\Extbase\Mvc\Web\ReferringRequest` has been deprecated.


Impact
======

Creating an instance of :php:`ReferringRequest` will trigger a PHP deprecation warning.


Affected Installations
======================

:php:`ReferringRequest` has been mostly extbase internal and rarely used in
extbase extensions, probably only in cases where
:php:`ActionController->forwardToReferringRequest()` is overridden.
The extension scanner will find usages with a strong match.

Migration
=========

Extbase internally, :php:`ReferringRequest` has only been used to
immediately create a :php:`ForwardResponse` from it. Consuming extensions
should follow his approach and create a :php:`ForwardResponse` directly.

.. index:: PHP-API, FullyScanned, ext:extbase
