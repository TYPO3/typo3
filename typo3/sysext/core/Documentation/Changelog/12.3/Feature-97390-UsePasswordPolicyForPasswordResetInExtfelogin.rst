.. include:: /Includes.rst.txt

.. _feature-97390-1667653394:

=======================================================================
Feature: #97390 - Use password policy for password reset in ext:felogin
=======================================================================

See :issue:`97390`

Description
===========

The password reset feature for TYPO3 frontend users now takes into account the
configurable password policy introduced in :ref:`#97388 <feature-97388>`,
if the feature toggle `security.usePasswordPolicyForFrontendUsers` is
set to `true` (default for new TYPO3 websites).

Impact
======

Password validation configured through
:typoscript:`plugin.tx_felogin_login.settings.passwordValidators` has been
marked as deprecated, but will still be used for password validation, if
the feature toggle `security.usePasswordPolicyForFrontendUsers` is set
to `false`.

TYPO3 websites, which have the feature toggle
`security.usePasswordPolicyForFrontendUsers` set to `true`, will use the globally
configured password policy when a TYPO3 frontend user resets their password.
The TYPO3 default password policy contains the following password requirements:

* At least 8 chars
* At least one number
* At least one upper case char
* At least one special char
* Must be different than current password (if available)

.. index:: Frontend, ext:felogin
