..  include:: /Includes.rst.txt

..  _deprecation-109409-1774774806:

================================================================
Deprecation: #109409 - Allowed paths configuration is deprecated
================================================================

See :issue:`109409`

Description
===========

Using :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']`
to configure additional public paths for the `typo3/app` package
has been deprecated.

Configure resources in :file:`config/system/resources.php` instead.
See :ref:`feature-109409-1774770383` for details.

Impact
======

TYPO3 installations that use
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']`
will receive a deprecation message whenever resources for the
`typo3/app` package are resolved.

Affected installations
======================

TYPO3 installations that use
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']`.

Migration
=========

Configure resources in :file:`config/system/resources.php` instead of
using :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']`.

See :ref:`feature-109409-1774770383` for information on how to
configure resources for the `typo3/app` package.

..  index:: PHP-API, NotScanned, ext:core
