..  include:: /Includes.rst.txt

..  _breaking-106869-1749659916:

=============================================================================
Breaking: #106869 - Remove static function parameter in AuthenticationService
=============================================================================

See :issue:`106869`

Description
===========

The method :php:`\TYPO3\CMS\Core\Authentication\AuthenticationService::processLoginData()`
no longer accepts the parameter :php:`$passwordTransmissionStrategy`.
Additionally, the method now declares a strict return type.

Impact
======

Authentication services extending or overriding
:php-short:`\TYPO3\CMS\Core\Authentication\AuthenticationService`
and its method :php:`processLoginData()` (or a subtype such as
:php:`processLoginDataBE()` or :php:`processLoginDataFE()`) will no longer
receive the :php:`$passwordTransmissionStrategy` parameter.

Affected installations
======================

TYPO3 installations with custom authentication services that extend
:php-short:`\TYPO3\CMS\Core\Authentication\AuthenticationService`
and implement or override :php:`processLoginData()` or one of its subtypes.

Migration
=========

Extensions extending
:php-short:`\TYPO3\CMS\Core\Authentication\AuthenticationService`
must remove the :php:`$passwordTransmissionStrategy` parameter from their
method signature and add the strict return type :php:`bool|int`.

Extensions implementing subtype methods such as
:php:`processLoginDataBE()` or :php:`processLoginDataFE()` must also remove the
parameter, as it is no longer passed to these methods.

..  index:: Backend, NotScanned, ext:core
