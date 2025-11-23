..  include:: /Includes.rst.txt

..  _breaking-107943-1761860828:

==========================================================================
Breaking: #107943 - Frontend and backend HTTP response compression removed
==========================================================================

See :issue:`107943`

Description
===========

The TYPO3 frontend and backend applications previously allowed compressing
their HTTP responses using the configuration options
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` and
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']`.

This feature, which was always disabled by default, has now been removed.

TYPO3 will no longer compress its HTTP responses itself.

Response compression should be handled by the web server rather than the
application layer.

Removing this feature avoids potential conflicts when both TYPO3 and the web
server attempt to compress responses and allows modern web servers to use
advanced compression algorithms such as brotli or zStandard when supported by
the client.

Impact
======

TYPO3 can no longer compress its HTTP responses.

This responsibility is now fully delegated to the web server.

HTTP response compression had to be explicitly enabled before, so most
installations will not notice a change unless they relied on this setting.

Affected installations
======================

Instances that configured
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` or
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']` to non-zero values
are affected.

Administrators should verify that the web server applies HTTP compression by
checking for a response header such as:

:code:`Content-Encoding: gzip`

when requesting frontend or backend documents with a header like:

:code:`Accept-Encoding: gzip, deflate`

All commonly used web servers enable this feature by default.

Migration
=========

The configuration toggles for the backend :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']`
and the frontend :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` are
obsolete, existing settings in :file:`settings.php` configuration files are
actively removed when first using the install tool after upgrade to TYPO3 v14.

..  index:: Backend, Frontend, LocalConfiguration, NotScanned, ext:core
