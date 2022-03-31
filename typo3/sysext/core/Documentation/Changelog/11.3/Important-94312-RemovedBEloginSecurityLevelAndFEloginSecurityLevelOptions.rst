.. include:: /Includes.rst.txt

===================================================================================
Important: #94312 - Removed BE/loginSecurityLevel and FE/loginSecurityLevel options
===================================================================================

See :issue:`94312`

Description
===========

The `FE/loginSecurityLevel` and `BE/loginSecurityLevel` options were used to
define the security level of the backend and frontend login. Since dropping
the two possibilities `challenged` and `superchallenged` in v7, `rsa` and
`normal` were the only two valid values left.

The `rsa` value however also became more or less obsolete, after dropping
`EXT:rsaauth` from Core in :issue:`87470`. Setting `rsa` therefore only had
effect in case the standalone `friendsoftypo3/rsaauth` extension was installed.

Finally, with :issue:`94279` also the support for the standalone
`friendsoftypo3/rsaauth` was abandoned, making the `loginSecurityLevel`
option superfluous, as `normal` was left as the only valid option.

Therefore, both options `FE/loginSecurityLevel` and `BE/loginSecurityLevel`
have been removed. As a result and to follow our backwards-compatibility promise,
all authentication services will still receive the `$passwordTransmissionStrategy`
argument in their :php:`processLoginData()` method, which however will now
always be `normal`.

Impact
======

The options have been removed from the TYPO3's default configuration.
When those options have been set in your :php:`LocalConfiguration.php`
or :php:`AdditionalConfiguration.php` files, they are automatically
removed when accessing the Install Tool or System Maintenance area.

.. index:: LocalConfiguration, ext:core
