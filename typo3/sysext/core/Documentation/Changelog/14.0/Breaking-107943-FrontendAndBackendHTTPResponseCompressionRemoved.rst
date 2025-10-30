..  include:: /Includes.rst.txt

..  _breaking-107943-1761860828:

==========================================================================
Breaking: #107943 - Frontend and backend HTTP response compression removed
==========================================================================

See :issue:`107943`

Description
===========

The TYPO3 frontend and backend application allowed to compress its HTTP responses
using the configuration toggles :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']`.

This feature was always disabled by default and has been removed: TYPO3 will no longer
compress its HTTP responses. Response compression should be applied by web servers and not
by the application layer. Removing this feature avoids collisions when both apply compression
and allows web servers to use improved algorithms like brotli or zStandard if HTTP clients
signal compatibility in HTTP requests.


Impact
======

TYPO3 can not compress its HTTP responses anymore and hands this task over to the
web server. HTTP response compression had to be actively enabled in instances.


Affected installations
======================

Instances that configured :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` or
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']` to non zero values may be
affected and should check whether the web server applies HTTP compression, indicated by
a HTTP response header like :code:`Content-Encoding: gzip` when requesting a frontend
and backend document with a HTTP header like :code:`Accept-Encoding: gzip, deflate`.
The default configuration of commonly used web servers enables this feature.


Migration
=========

The configuration toggle for the Backend :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']`
is obsolete, existing settings in :file:`settings.php` configuration files are actively removed
when first using the install tool after upgrade to TYPO3 v14. Its counterpart for the Frontend
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` is kept since it is
still used to enable frontend resource pre-compression (JS and CSS files) among further
configuration using TypoScript.


..  index:: Backend, Frontend, LocalConfiguration, NotScanned, ext:core
