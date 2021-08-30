.. include:: ../../Includes.txt

=====================================================================
Feature: #94402 - Generated Error pages via TYPO3-internal subrequest
=====================================================================

See :issue:`94402`

Description
===========

Error pages (such as 404 - not found, or 403 - access denied) are now generated
via a TYPO3-internal sub-request instead of an external HTTP
request (cURL over Guzzle).

The internal generation of the error pages is enabled by default, but can
be disabled via a feature flag called "subrequestPageErrors" in the "Settings"
module.


Impact
======

Generating error pages internally reduces the server load drastically, and
solves various issues when dealing with load-balanced systems where the hostname
of the server might not match the public-facing server ("front server").

However, in some rare cases there might be problems with third-party extensions
that override super globals (e.g. $_GET and $_POST), where the option could be
disabled.

This feature is only relevant for site configurations loading error pages
from a different Page ID.

.. index:: Frontend, PHP-API, ext:frontend
