.. include:: /Includes.rst.txt

==========================================================================
Deprecation: #85833 - Extension saltedpasswords merged into core extension
==========================================================================

See :issue:`85833`

Description
===========

`EXT:saltedpasswords` has been merged into the `core` extension. All
classes have been moved to the PHP namespace :php:`TYPO3\CMS\Core\Crypto\PasswordHashing`.

The documentation has been moved to the Core API document and can be found
`online <https://docs.typo3.org/typo3cms/CoreApiReference/stable/ApiOverview/PasswordHashing/>`_.

Classes that have been marked as deprecated have been moved to the same namespace and will be removed in TYPO3 v10.

The following classes have been renamed:

* :php:`TYPO3\CMS\Saltedpasswords\Salt\Argon2iSalt::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash::class`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BcryptSalt::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash::class`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash::class`
* :php:`TYPO3\CMS\Saltedpasswords\Exception\InvalidSaltException::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException::class`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash::class`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::class`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\SaltInterface::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface::class`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::class`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash::class`
* (deprecated) :php:`TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\AbstractComposedSalt::class`
* (deprecated) :php:`TYPO3\CMS\Saltedpasswords\Salt\ComposedSaltInterface::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\ComposedPasswordHashInterface::class`
* (deprecated) :php:`TYPO3\CMS\Saltedpasswords\Utility\ExensionManagerConfigurationUtility::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\ExtensionManagerConfigurationUtility::class`
* (deprecated) :php:`TYPO3\CMS\Saltedpasswords\SaltedPasswordService::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordService::class`
* (deprecated) :php:`TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::class` to :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordsUtility::class`

The following language files have been moved:

* (deprecated) :file:`saltedpasswords/Resources/Private/Language/locallang.xlf` to :file:`core/Resources/Private/Language/locallang_deprecated_saltedpasswords.xlf`
* (deprecated) :file:`saltedpasswords/Resources/Private/Language/locallang_em.xlf` to :file:`core/Resources/Private/Language/locallang_deprecated_saltedpasswords_em.xlf`

Impact
======

This change is usually transparent for TYPO3 instances. The old class names have been defined as
aliases to the new names. They will continue to work in TYPO3 v9 and be dropped in TYPO3 v10.


Affected Installations
======================

Almost no instance is directly affected by this change, most instances need no configuration change.
In rare cases, if extensions directly deal with password hashing, class namespaces may need to be adapted.
The extension scanner will find usages of old class names.


Migration
=========

Use the new class names and drop usages of deprecated classes.

.. index:: Backend, PHP-API, FullyScanned, ext:saltedpasswords
