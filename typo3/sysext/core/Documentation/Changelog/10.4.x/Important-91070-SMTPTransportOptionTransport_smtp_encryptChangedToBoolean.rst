.. include:: /Includes.rst.txt

=====================================================================================
Important: #91070 - SMTP transport option 'transport_smtp_encrypt' changed to boolean
=====================================================================================

See :issue:`91070`

Description
===========

With https://forge.typo3.org/issues/90295 the allowed value for
:php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt']` has been
changed to a boolean value.

symfony/mailer does no longer allow to specify the `STARTTLS` usage, as it will
be used by default (if the server provides the needed support).

Therefore, the SMTP encryption configuration setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt']` is
automatically updated by the install tool's silent configuration upgrade.

The configuration value `(string)tls` is removed to reflect that symfony/mailer
expects `(bool)false` for `STARTTLS`. Other values like `(string)ssl` are
converted too `(bool)true`.

No migration is needed at all, as no deprecation is thrown.

.. index:: LocalConfiguration, ext:core
