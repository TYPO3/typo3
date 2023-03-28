.. include:: /Includes.rst.txt

.. _important-100032-1677331239:

=====================================================================
Important: #100032 - Add HTTP security headers for backend by default
=====================================================================

See :issue:`100032`

Description
===========

The following HTTP security headers are now added by default for the TYPO3
backend:

* `Strict-Transport-Security: max-age=31536000` (only if
  :php:`$GLOBALS[TYPO3_CONF_VARS][BE][lockSSL]` is active)
* `X-Content-Type-Options: nosniff`
* `Referrer-Policy: strict-origin-when-cross-origin`

The default HTTP security headers are configured globally in
`$GLOBALS['TYPO3_CONF_VARS']['BE']['HTTP']['Response']['Headers']` and include
a unique array key, so it is possible to individually unset/remove unwanted
headers.

..  important::

    TYPO3 websites, which already use custom HTTP headers for the TYPO3 backend,
    must ensure that individual HTTP security headers are not sent multiple
    times.

.. index:: Backend, ext:backend
