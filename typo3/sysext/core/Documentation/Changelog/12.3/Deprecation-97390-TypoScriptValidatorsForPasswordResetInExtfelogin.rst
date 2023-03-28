.. include:: /Includes.rst.txt

.. _deprecation-97390-1667657114:

=============================================================================
Deprecation: #97390 - TypoScript validators for password reset in ext:felogin
=============================================================================

See :issue:`97390`

Description
===========

The TypoScript password validation configured through
:typoscript:`plugin.tx_felogin_login.settings.passwordValidators` has been
marked as deprecated.

The TypoScript validators are used when the feature toggle
`security.usePasswordPolicyForFrontendUsers` is set to `false` (default for
existing TYPO3 installations).

An upgrade wizard will ask the user during the TYPO3 upgrade
if `security.usePasswordPolicyForFrontendUsers` should be activated or, if
deprecated, TypoScript validators should be used.

Impact
======

Validators configured in
:typoscript:`plugin.tx_felogin_login.settings.passwordValidators` will
trigger a deprecation log entry when a password reset is performed.


Affected installations
======================

TYPO3 installations using validators configured in
:typoscript:`plugin.tx_felogin_login.settings.passwordValidators`.


Migration
=========

Special password requirements configured using custom validators in TypoScript
must be migrated to a custom password policy validator as described
in :ref:`#97388 <feature-97388>`.

Before creating a custom password policy validator, it is recommended to
check if the :php:`CorePasswordValidator` used in the default password
policy suits current password requirements.

.. index:: Frontend, NotScanned, ext:felogin
