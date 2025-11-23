..  include:: /Includes.rst.txt

..  _feature-105783-1733506414:

===========================================================================
Feature: #105783 - Notify backend users on failed MFA verification attempts
===========================================================================

See :issue:`105783`

Description
===========

TYPO3 now notifies backend users by email when a failed MFA (multi-factor
authentication) verification attempt occurs. The notification is sent only if
an MFA provider is configured and the user has a valid email address in their
profile.

Impact
======

TYPO3 backend users now benefit from improved security awareness through
immediate email notifications about failed MFA verification attempts. This
feature is particularly useful in cases where backend accounts with active MFA
configuration are targeted by unauthorized access attempts.

..  index:: Backend, ext:backend
