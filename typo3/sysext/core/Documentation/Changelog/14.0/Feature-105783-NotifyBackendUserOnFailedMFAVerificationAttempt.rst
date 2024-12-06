..  include:: /Includes.rst.txt

..  _feature-105783-1733506414:

==========================================================================
Feature: #105783 - Notify backend user on failed MFA verification attempts
==========================================================================

See :issue:`105783`

Description
===========

TYPO3 now notifies backend users via email when a failed MFA (Multi-Factor
Authentication) verification attempt occurs. The notification is sent only if
an MFA provider is configured and the user has a valid email address in their
profile.


Impact
======

TYPO3 backend users benefit from enhanced security awareness through immediate
email notifications about failed MFA verification attempts. This is especially
useful in scenarios where backend accounts with active MFA setup are targeted
by unauthorized access attempts.

..  index:: Backend, ext:backend
