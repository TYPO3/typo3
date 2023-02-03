.. include:: /Includes.rst.txt

.. _important-95297-1674809371:

========================================================
Important: #95297 - Strict cHash validation feature flag
========================================================

See :issue:`95297`

Description
===========

Since TYPO3 v9 and the PSR-15 Middleware concept, cHash validation was moved
outside of plugins and rendering code inside a validation middleware to check if
a given "cHash" acts as a signature of other query parameters in order to use a
cached version of a frontend page.

However, the check only provided information about an invalid "cHash" in the
query parameters. When no "cHash" was given, the only option was to add a
"required list" (global TYPO3 configuration option
`requireCacheHashPresenceParameters`), but not based on the final
`excludedParameters` for cache hash calculation of given query parameters.

For this reason, a new global TYPO3 configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation']`
has been added.

When enabled, the same validation for calculating a "cHash" value is used as
when a valid or invalid "cHash" parameter is given to a request, even when no
"cHash" is given.

The new option is disabled for existing installations, but enabled for new
installations. It is also highly recommended to enable this option in
your existing installations.

In future TYPO3 versions, this functionality will be enabled for all TYPO3
installations, while the configuration option
`requireCacheHashPresenceParameters` will be removed.

.. index:: Frontend, LocalConfiguration, ext:frontend
