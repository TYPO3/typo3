.. include:: ../../Includes.txt

=====================================================================
Breaking: #79302 - Moved pages.url_scheme to compatibility7 extension
=====================================================================

See :issue:`79302`

Description
===========

The database field "pages.url_scheme" functionality has been moved to the compatibility7 extension.

The field allows to force the HTTP or HTTPS protocol for a specific page to be set by an editor in the page properties on a per-page
basis. However, it is common today to ensure (if a SSL certificate is available) to use HTTPS for a whole website or even only for a
specific area (inc. subpages) to force the protocol.


Impact
======

If the functionality was used before, it will not work anymore, thus links will not be forced to be generated with a forced HTTP/HTTPS url
scheme and redirects on pages that had the option set will not happen anymore, unless the compatibility7 extension is installed.

Generating preview links with pages that have an enforced scheme out of the TYPO3 backend will not work anymore.


Affected Installations
======================

Any TYPO3 instance that depends on the `url_scheme` database field, having any value filled in.


Migration
=========

Install the compatibility7 extension to have the same functionality as before, or use HTTPS enforcing via server configuration (.htaccess)
or any SSL related extension in the TYPO3 Extension Repository (TER) that provides superior functionality.

To ensure a certain protocol when previewing a page the TSconfig option `TCEMAIN.previewDomain` can be used to set a preview prefix including
the URL scheme.

.. index:: Database, Frontend