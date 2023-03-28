.. include:: /Includes.rst.txt

.. _deprecation-99810-1675704638:

==================================================================
Deprecation: #99810 - "versionNumberInFilename" option now boolean
==================================================================

See :issue:`99810`

Description
===========

The system-wide setting :php:`$TYPO3_CONF_VARS['FE']['versionNumberInFilename']`
was previously evaluated as a "string" value, having three possible options:

* ""
* "querystring"
* "embed"

Depending on the option, resources used in TYPO3's frontend templates, such as
JavaScript or CSS assets, had their "modification time" in either the
querystring (:samp:`myfile.js?1675703622`), or in the file name itself
:samp:`myfile.1675703622.js` - the "embed" option). The latter option required a
:file:`.htaccess` rule.

This existing feature ("cachebusting") is especially important for proxy / CDN
setups.

For the sake of simplicity, the option is now a boolean option - and behaves
similarly to the backend variant :php:`$TYPO3_CONF_VARS['BE']['versionNumberInFilename']`.


Impact
======

If the option is now set to "false", it behaves as "querystring" did before, setting
it to "true", the feature behaves exactly as "embed". The original empty option
is removed, so all assets within the TYPO3 frontend rendering always include
cachebusting, by default a querystring, which is fully backwards-compatible.


Affected installations
======================

TYPO3 installations that have actively set this option in
:file:`LocalConfiguration.php`, :file:`AdditionalConfiguration.php` or in an
extension :file:`ext_localconf.php`.


Migration
=========

When updating TYPO3 and accessing the maintenance area, an explicitly set option
is automatically migrated. If this is not possible - for example, configuration in
:file:`AdditionalConfiguration.php` is set - the value is always migrated
on-the-fly when the setting is evaluated.

.. index:: LocalConfiguration, PHP-API, NotScanned, ext:core
