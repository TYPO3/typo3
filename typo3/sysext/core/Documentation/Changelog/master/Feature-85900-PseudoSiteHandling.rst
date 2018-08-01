.. include:: ../../Includes.txt

======================================
Feature: #85900 - Pseudo Site Handling
======================================

See :issue:`85900`

Description
===========

The new site handling functionality has a counterpart for usages within PHP code where no site configuration
can be found, which is named "Pseudo Site", a site without configuration.

For a pseudo-site it is not possible to determine all available languages (as they are only configured in
TypoScript), or the proper labels for the default language (as this is done in PageTSconfig), however, a
PseudoSite or Site object (both instances of "SiteInterface") is now always attached to every Frontend or
Backend request via a PSR-15 middleware.


Impact
======

Extension Developers can now access a site and determine the base URL / Entry Point URL for a site, or access
all available languages via the SiteInterface object, instead of querying sys_domain or sys_language respectively.

.. index:: PHP-API