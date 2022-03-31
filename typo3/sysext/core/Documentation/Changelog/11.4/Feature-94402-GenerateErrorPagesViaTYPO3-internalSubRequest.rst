.. include:: /Includes.rst.txt

=====================================================================
Feature: #94402 - Generate error pages via TYPO3-internal sub-request
=====================================================================

See :issue:`94402`

Description
===========

Error pages (such as 404 - not found, or 403 - access denied) may now be generated
via a TYPO3-internal sub-request instead of an external HTTP
request (cURL over Guzzle).

This feature is disabled by default, as there are some cases where stateful information
is not correctly reset for the subrequest.  It may be enabled on an experimental
basis via a feature flag called `subrequestPageErrors` in the "Settings"
module.

This change will default to enabled in a future version once all stateful services
are identified and removed.

Impact
======

Generating error pages internally reduces the server load drastically, and
solves various issues when dealing with load-balanced systems where the hostname
of the server might not match the public-facing server ("front server").

However, in some cases there might be problems with third-party extensions
that override super globals (e.g. :php:`$_GET` and :php:`$_POST`), where the option could be
disabled.  There are also some remaining cases in core of stateful services that,
in some configurations, result in incorrect error pages being generated. For that
reason the feature defaults to disabled for now.

This feature is only relevant for site configurations loading error pages
from a different Page ID.

.. index:: Frontend, PHP-API, ext:frontend
