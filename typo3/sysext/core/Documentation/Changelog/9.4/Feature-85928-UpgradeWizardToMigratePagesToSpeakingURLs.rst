.. include:: ../../Includes.txt

==================================================================
Feature: #85928 - Upgrade wizard to migrate pages to speaking URLs
==================================================================

See :issue:`85928`

Description
===========

TYPO3 now supports "Speaking URLs" for pages, and in order to fully make use of this feature, an
upgrade wizard builds up the URL segment (pagepath) for all pages that do not have a value
set already.

In order to ease the pain when upgrading from previous versions that supported RealURL,
the upgrade wizard checks for additional tables "tx_realurl_pathcache" (realurl v1) and
"tx_realurl_pathdata" (realurl v2+). If they exist in the database, these values are used to fill the page paths,
however they will get sanitized to match the slug layout with a prefixed "/".

Pages that contain value in their "alias" database field, this takes priority over "regular" pages
and values from RealURL, whereas alias fields will result in a slug like "/my-alias-value".


Impact
======

After running the upgrade wizard, it is possible to use all of the speaking URL functionality for
all pages that support a site configuration.

The upgrade wizard also runs through all pages that do not have a site configuration yet, in
order to ensure consistent state throughout the database. It is encouraged to create a site
configuration for a pagepath before running this upgrade wizard.

Please take note that running the upgrade wizard does not migrate a previously configured RealURL
project fully to the new structure. It only eases the migration, but the full migration depends
on many more previous URL generation configurations used.

Also: if `simulate_static`, `realurl` or `cooluri` or any other extension for URL rewriting was
used, it is highly possible that pages are now available under different URLs than before.

.. index:: Database
