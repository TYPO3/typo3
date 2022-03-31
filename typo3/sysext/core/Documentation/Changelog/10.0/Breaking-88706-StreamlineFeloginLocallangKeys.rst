.. include:: /Includes.rst.txt

====================================================
Breaking: #88706 - Streamline felogin locallang keys
====================================================

See :issue:`88706`

Description
===========

Remove  `ll_` prefixes from translation keys in :file:`ext:felogin/Resources/private/Language/locallang.xlf` so that they share the same identifiers with the flexform settings.


Impact
======

Breaks installations that override ext:felogin language keys that are prefixed with `ll_`.


Affected Installations
======================

Only installations that override one of the following keys via TypoScript are affected.

Keys:

- `ll_welcome_header`
- `ll_welcome_message`
- `ll_logout_header`
- `ll_logout_message`
- `ll_error_header`
- `ll_error_message`
- `ll_success_header`
- `ll_success_message`
- `ll_status_header`
- `ll_status_message`
- `ll_change_password_header`
- `ll_change_password_message`
- `ll_change_password_nolinkprefix_message`
- `ll_change_password_notvalid_message`
- `ll_change_password_notequal_message`
- `ll_change_password_tooshort_message`
- `ll_change_password_done_message`
- `ll_forgot_header`
- `ll_forgot_email_password`
- `ll_forgot_email_nopassword`
- `ll_forgot_validate_reset_password`
- `ll_forgot_message`
- `ll_forgot_message_emailSent`
- `ll_forgot_reset_message`
- `ll_forgot_reset_message_emailSent`
- `ll_forgot_reset_message_error`
- `ll_forgot_header_backToLogin`
- `ll_enter_your_data`


Migration
=========

Remove the `ll_` prefix from the key.

.. index:: Frontend, NotScanned, ext:felogin
