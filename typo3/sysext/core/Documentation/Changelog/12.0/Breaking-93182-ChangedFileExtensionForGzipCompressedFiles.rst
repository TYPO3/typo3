.. include:: /Includes.rst.txt

.. _breaking-93182-1651654104:

===================================================================
Breaking: #93182 - Changed file extension for gzip compressed files
===================================================================

See :issue:`93182`

Description
===========

When using file compression for resources such as JavaScript or
StyleSheets via :php:`$GLOBALS[TYPO3_CONF_VARS][FE][compressionLevel]` or
:php:`$GLOBALS[TYPO3_CONF_VARS][BE][compressionLevel]` the generated files are
now written via the file extension ".gz" instead of ".gzip" in previous versions.

TYPO3 follows the de-facto standard for compressed assets,
as ".gz" is much more widespread than ".gzip" file extensions
(see https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types).

Impact
======

Compressed resources are now generated and served via ".gz".

Affected installations
======================

TYPO3 installations setting the global configuration option.

Migration
=========

Adapt possible :file:`.htaccess` or other webserver configuration files
by replacing ".gzip" with ".gz" if this feature is activated.

.. index:: Backend, Frontend, NotScanned, ext:core
