.. include:: /Includes.rst.txt

=============================================================
Deprecation: #85804 - Salted password hash class deprecations
=============================================================

See :issue:`85804`

Description
===========

Selecting the hash algorithm used to store frontend and backend user hashes is
now a "preset" and can be changed using "Admin tools" -> "Settings" -> "Configuration Presets".

Existing settings are updated automatically when upgrading from an older TYPO3 version to
TYPO3 v9. The detail list below is only interesting for instances that need to
run custom hash mechanisms.

The password hash mechanism used for backend user passwords has been moved from
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['BE']['saltedPWHashingMethod']`
to :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['className']`. Options for a specific
hash algorithms can be defined using :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['options']`.

The password hash mechanism used for frontend user passwords has been moved from
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['FE']['saltedPWHashingMethod']`
to :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['className']`. Options for a specific
hash algorithms can be defined using :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['options']`.

Custom password hash algorithms should now be registered in
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms']`.
The usage of the former array entry
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods']` has been marked as deprecated.

These interfaces and classes have been marked as deprecated and should not be implemented any longer:

* :php:`TYPO3\CMS\Saltedpasswords\Salt\ComposedSaltInterface`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Utility\ExtensionManagerConfigurationUtility`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Utility\SaltedPasswordsUtility`

An interface has been changed:

* :php:`TYPO3\CMS\Saltedpasswords\Salt\SaltInterface->getHashedPassword(string $password)` - The
  second argument has been dropped. Classes implementing the interface should remove the second argument.

These methods have been marked as deprecated:

* :php:`TYPO3\CMS\Saltedpasswords\Salt\Argon2iSalt->getOptions()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Argon2iSalt->setOptions()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BcryptSalt->getOptions()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BcryptSalt->setOptions()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->getHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->getMaxHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->getMinHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->getSaltLength()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->getSetting()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->setHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->setMaxHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->setMinHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt->getSetting()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt->getSaltLength()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->getHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->getMaxHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->getMinHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->getSaltLength()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->getSetting()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->setHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->setMaxHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->setMinHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->getHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->getMaxHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->getMinHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->getSaltLength()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->getSetting()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->setHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->setMaxHashCount()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->setMinHashCount()`

These methods changed their signature:

* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->getHashedPassword()` - Second argument marked as deprecated
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt->getHashedPassword()` - Second argument marked as deprecated
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->getHashedPassword()` - Second argument marked as deprecated
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->getHashedPassword()` - Second argument marked as deprecated

These methods changed their visibility from public to protected:

* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->isValidSalt()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->base64Encode()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt->isValidSalt()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt->base64Encode()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->isValidSalt()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->base64Encode()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->base64Decode()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->isValidSalt()`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->base64Encode()`

These class constants have been marked as deprecated and will be removed in TYPO3 v10:

* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::ITOA64`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::MAX_HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::MIN_HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::ITOA64`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::ITOA64`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::MAX_HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::MIN_HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::ITOA64`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::MAX_HASH_COUNT`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::MIN_HASH_COUNT`


Impact
======

Using functionality from the above list will trigger PHP :php:`E_USER_DEPRECATED` errors.


Affected Installations
======================

Almost no TYPO3 instances are directly affected by the changes outlined above. A configuration
upgrade is in place to move from old to new settings when calling the install tool the first time
after upgrade without further user interaction.

If in rare cases an existing TYPO3 instance runs custom salt mechanisms, the extension scanner
will find affected code places that should be adapted.


Migration
=========

If the extension scanner finds affected code, adapt the method calls, class constant usages and interface usages.

.. index:: PHP-API, FullyScanned, ext:saltedpasswords
