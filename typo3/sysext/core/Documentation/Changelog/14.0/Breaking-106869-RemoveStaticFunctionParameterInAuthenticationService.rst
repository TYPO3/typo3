..  include:: /Includes.rst.txt

..  _breaking-106869-1749659916:

=============================================================================
Breaking: #106869 - Remove static function parameter in AuthenticationService
=============================================================================

See :issue:`106869`

Description
===========

The parameter :php:`$passwordTransmissionStrategy`of the function
:php:`TYPO3\CMS\Core\Authentication\processLoginData` has been removed.
Additionally, the function does now use a strict return type.


Impact
======

Authentication services extending
:php:`TYPO3\CMS\Core\Authentication\processLoginData` or implementing a
subtype (e.g. :php:`processLoginDataBE` or :php:`processLoginDataFE`) will not
be called with the :php:`$passwordTransmissionStrategy` parameter any more.


Affected installations
======================

Authentication services extending :php:`TYPO3\CMS\Core\Authentication\processLoginData`
or implementing a subtype of the function.


Migration
=========

Extension extending :php:`TYPO3\CMS\Core\Authentication\processLoginData` must
remove the parameter and additionally add :php:`bool|int` as return type for the
function.

Extensions implementing a subtype of the function should remove the parameter,
as it it not passed to subtype functions any more.

..  index:: Backend, NotScanned, ext:core
