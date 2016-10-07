
.. include:: ../../Includes.txt

=====================================================================
Breaking: #70056 - Http-related options and HttpRequest class removed
=====================================================================

See :issue:`70056`

Description
===========

The following PHP classes have been removed:

   * `TYPO3\CMS\Core\Http\HttpRequest`
   * `TYPO3\CMS\Core\Http\Observer\Download`

The following configuration options have been removed:

   * $TYPO3_CONF_VARS[SYS][curlUse]
   * $TYPO3_CONF_VARS[SYS][curlProxyNTLM]
   * $TYPO3_CONF_VARS[SYS][curlProxyServer]
   * $TYPO3_CONF_VARS[SYS][curlProxyTunnel]
   * $TYPO3_CONF_VARS[SYS][curlProxyUserPass]
   * $TYPO3_CONF_VARS[SYS][curlTimeout]
   * $TYPO3_CONF_VARS[HTTP][adapter]
   * $TYPO3_CONF_VARS[HTTP][protocol_version]
   * $TYPO3_CONF_VARS[HTTP][follow_redirects]
   * $TYPO3_CONF_VARS[HTTP][max_redirects]
   * $TYPO3_CONF_VARS[HTTP][strict_redirects]
   * $TYPO3_CONF_VARS[HTTP][proxy_host]
   * $TYPO3_CONF_VARS[HTTP][proxy_port]
   * $TYPO3_CONF_VARS[HTTP][proxy_user]
   * $TYPO3_CONF_VARS[HTTP][proxy_password]
   * $TYPO3_CONF_VARS[HTTP][proxy_auth_scheme]
   * $TYPO3_CONF_VARS[HTTP][ssl_verify_peer]
   * $TYPO3_CONF_VARS[HTTP][ssl_verify_host]
   * $TYPO3_CONF_VARS[HTTP][ssl_cafile]
   * $TYPO3_CONF_VARS[HTTP][ssl_capath]
   * $TYPO3_CONF_VARS[HTTP][ssl_local_cert]
   * $TYPO3_CONF_VARS[HTTP][ssl_passphrase]
   * $TYPO3_CONF_VARS[HTTP][userAgent]

The following properties have been renamed:

   * $TYPO3_CONF_VARS[HTTP][userAgent] is now called $TYPO3_CONF_VARS[HTTP][headers][User-Agent]
   * $TYPO3_CONF_VARS[HTTP][protocol_version] is now called $TYPO3_CONF_VARS[HTTP][version]
   * All proxy-related options are unified within $TYPO3_CONF_VARS[HTTP][proxy]
   * All redirect-related options (HTTP/follow_redirects, HTTP/max_redirects, HTTP/strict_redirects) are unified within $TYPO3_CONF_VARS[HTTP][allow_redirects]
   * All options related to SSL private keys (HTTP/ssl_local_cert, HTTP/ssl_passphrase) are merged into $TYPO3_CONF_VARS[HTTP][ssl_key]
   * All options related to verify SSL peers are merged into $TYPO3_CONF_VARS[HTTP][verify]

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

All still necessary options will be migrated to new options within `$TYPO3_CONF_VARS[HTTP]` when the install tool is run.

In special cases, the options `$TYPO3_CONF_VARS[HTTP][ssl_verify_host]`, `$TYPO3_CONF_VARS[HTTP][proxy_auth_scheme]`
and `$TYPO3_CONF_VARS[HTTP][proxy_host]` need to migrated manually to the newly available options.

.. index:: PHP-API, LocalConfiguration
