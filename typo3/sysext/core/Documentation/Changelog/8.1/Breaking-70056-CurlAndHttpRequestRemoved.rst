
.. include:: /Includes.rst.txt

=====================================================================
Breaking: #70056 - Http-related options and HttpRequest class removed
=====================================================================

See :issue:`70056`

Description
===========

The following PHP classes have been removed:

* :php:`TYPO3\CMS\Core\Http\HttpRequest`
* :php:`TYPO3\CMS\Core\Http\Observer\Download`

The following configuration options have been removed:

* :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][curlUse]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][curlProxyNTLM]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][curlProxyServer]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][curlProxyTunnel]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][curlProxyUserPass]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][curlTimeout]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][adapter]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][protocol_version]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][follow_redirects]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][max_redirects]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][strict_redirects]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy_host]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy_port]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy_user]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy_password]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy_auth_scheme]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_verify_peer]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_verify_host]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_cafile]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_capath]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_local_cert]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_passphrase]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][userAgent]`

The following properties have been renamed:

* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][userAgent]` is now called :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][headers][User-Agent]`
* :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][protocol_version]` is now called :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][version]`
* All proxy-related options are unified within :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy]`
* All redirect-related options (HTTP/follow_redirects, HTTP/max_redirects, HTTP/strict_redirects) are unified within :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][allow_redirects]`
* All options related to SSL private keys (HTTP/ssl_local_cert, HTTP/ssl_passphrase) are merged into :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_key]`
* All options related to verify SSL peers are merged into :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][verify]`

Additionally, the dependency to the PEAR Package "Http_Request2" (composer package name `pear/http_request2`) has
been removed in favor of the PHP library Guzzle.


Impact
======

Calling the mentioned classes above will result in a fatal PHP error.

Using the options in custom PHP code will result in unexpected behavior as the options are non-existent and empty.

Using PHP code that depends on the removed PEAR library "Http_Request2" will result in unexpected behaviour and possibly a
fatal PHP error.


Affected Installations
======================

All 3rd party extensions calling the mentioned classes directly or using the configuration options directly, as well
as installations depending on the PEAR library "Http_Request2".


Migration
=========

For PHP code previously using the `HttpRequest` and `Download` classes a new object-oriented PSR-7-based approach is
introduced, see the Guzzle Feature integration documentation for more details. A new PHP class
`TYPO3\CMS\Core\Http\RequestFactory` which generates PSR-7 compliant request objects helps in simplifying the
migration process.

All still necessary options will be migrated to new options within :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP]` when the install tool is run.

In special cases, the options :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][ssl_verify_host]`, :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy_auth_scheme]`
and :php:`$GLOBALS[TYPO3_CONF_VARS][HTTP][proxy_host]` need to migrated manually to the newly available options.

.. index:: PHP-API, LocalConfiguration
