.. include:: /Includes.rst.txt

.. _deprecation-100232-1679344508:

=========================================================
Deprecation: #100232 - $TBE_STYLES skinning functionality
=========================================================

See :issue:`100232`

Description
===========

The global configuration array :php:`$TBE_STYLES` has been deprecated in favor of a new
setting :php:`$TYPO3_CONF_VARS['BE']['stylesheets']`. Previously, mainly before
TYPO3 6.0, :php:`$TBE_STYLES` allowed for defining more styles within PHP instead
of using CSS.
However, now that CSS has become been much more powerful than 10 years ago,
it is time to adjust the logic and also consolidate TYPO3's internal configuration
settings.

This deprecation is done in order to be more flexible for styling purposes, as
the registration of custom stylesheets can now also be handled on a per-project
basis.

Extensions can use almost the same syntax, however the registration is now done
in an extensions' :file:`ext_localconf.php` to reduce the loading times to
load :file:`ext_tables.php` files.


Impact
======

Registration of Backend styles via :php:`$GLOBALS['TBE_STYLES']['skins']` in
an extensions' :file:`ext_tables.php` file will trigger a PHP
deprecation notice.

Setting :php:`$GLOBALS['TBE_STYLES']['stylesheets']['admPanel']` will also
trigger a deprecation notice every time the Admin Panel is loaded in the
TYPO3 Frontend.


Affected installations
======================

TYPO3 installations with custom styling for the TYPO3 Backend or the Admin Panel
via :php:`$GLOBALS['TBE_STYLES']`.


Migration
=========

Migrate to the new configuration setting :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']`
which can be set per site or within an extensions' :file:`ext_localconf.php`.

For a custom stylesheet of the TYPO3 Admin Panel, it is recommended to use the
new AdminPanel Module API (available since TYPO3 v9 LTS) where custom CSS and
JavaScript files can be registered dynamically.

.. index:: Backend, LocalConfiguration, PHP-API, FullyScanned, ext:backend
