.. include:: ../../Includes.txt

========================================================================
Important: #85833 - saltedpasswords extension merged into core extension
========================================================================

See :issue:`85833`

Description
===========

The previously available system extension `saltedpasswords` has been removed as its
functionality has been merged into `core`. All functionality is still available but
renamed related to a better Hashing API.

This resolves a hard cross-dependency between `EXT:core` and `EXT:saltedpasswords`
since TYPO3 v6 as it was a sane default to properly use password hashes.

Backwards compatibility is given by automatic upgrades of settings when visiting
the Install Tool.

For composer-based installations this means the dependency to `typo3/cms-saltedpasswords`
can safely be removed via :shell:`composer remove typo3/cms-saltedpasswords`, although this is
not mandatory due to the fact that `typo3/cms-core` is noted as a replacement for
saltedpasswords in its :file:`composer.json` file.

In some edge-cases for non-composer-based installations it might be necessary to remove
the `saltedpasswords` entry from :php:`typo3conf/PackagesStates.php`.

Any checks for :php:`ExtensionManagementUtility::isLoaded('saltedpasswords')` in
third-party extensions which were not necessary (as saltedpasswords had to be installed
at any time anyways), can safely be removed.

.. index:: Backend, NotScanned, ext:saltedpasswords
