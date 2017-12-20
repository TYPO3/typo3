.. include:: ../../Includes.txt

===============================================================================
Breaking: #83294 - Salted Passwords: Custom saltings must use the SaltInterface
===============================================================================

See :issue:`83294`

Description
===========

The salted passwords factory allowed to register custom saltings has been changed. All custom salts
need to implement :php:`TYPO3\CMS\SaltedPasswords\Salt\SaltInterface`. Before, this was
handled by extending from :php:`TYPO3\CMS\SaltedPasswords\Salt\AbstractSalt`, which has been renamed to
:php:`TYPO3\CMS\SaltedPasswords\Salt\AbstractComposedSalt` when the salting is implemented.


Impact
======

When writing custom salts for TYPO3, they need to implement the SaltInterface.

If extending from :php:`AbstractSalt`, custom salt now need to extend from :php:`AbstractComposedSalt` and
implement the additional method :php:`getSaltLength()` and :php:`isValidSalt($salt)`.


Affected Installations
======================

TYPO3 installations using custom salts for `EXT:saltedpasswords`.


Migration
=========

Switch to the new implemention details mentioned above, and change your custom salt to fit
to the :php:`SaltInterface` API.

.. index:: PHP-API, NotScanned, ext:saltedpasswords