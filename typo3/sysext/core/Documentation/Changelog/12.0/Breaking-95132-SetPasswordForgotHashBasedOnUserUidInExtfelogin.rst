.. include:: /Includes.rst.txt

.. _breaking-95132-1659375274:

============================================================================
Breaking: #95132 - Set password forgot hash based on user uid in ext:felogin
============================================================================

See :issue:`95132`

Description
===========

The signature of the :php:`sendRecoveryEmail()` function in the
:php:`TYPO3\CMS\FrontendLogin\Service\RecoveryService` has changed. The function
now requires 2 arguments in order to support scenarios for multi-site TYPO3
setups with multiple storage folders for users with the same email address.

Additionally, the :php:`RecoveryService` class now does not implement
:php:`TYPO3\CMS\FrontendLogin\Service\RecoveryServiceInterface` any more, since
the interface has been removed.

Impact
======

3rd party extensions implementing :php:`RecoveryService` have to be adapted
manually to support the new function signature.

3rd party extensions implementing :php:`RecoveryServiceInterface` have to be
adapted manually to extend :php:`RecoveryService` instead.

Affected installations
======================

3rd party extensions implementing :php:`RecoveryService` and
:php:`RecoveryServiceInterface`.

Migration
=========

Custom implementations of :php:`RecoveryService` must be adopted to support the new
function signature :php:`sendRecoveryEmail(array $userData, string $hash)`.

Custom implementations of :php:`RecoveryServiceInterface` must be adopted to
extend :php:`RecoveryService` instead.

.. index:: Frontend, PHP-API, NotScanned, ext:felogin
