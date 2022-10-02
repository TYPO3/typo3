.. include:: /Includes.rst.txt

.. _breaking-97550-1651697278:

========================================================================
Breaking: #97550 - TypoScript option config.disableCharsetHeader removed
========================================================================

See :issue:`97550`

Description
===========

The TypoScript flag :typoscript:`config.disableCharsetHeader` has been completely removed
from TYPO3 Core.

This option was used to avoid sending HTTP headers of type `Content-Type` to
the client. This flag was mainly used to overcome a technical limitation to
override the Content-Type information back in TYPO3 v4.x.

Impact
======

TYPO3 now always sends the `Content-Type` header to the client in the TYPO3
Frontend.

Affected installations
======================

TYPO3 installations having this option enabled via TypoScript.

Migration
=========

It is not needed to set this option. Even when Extbase plugins return JSON-based
Responses, the Content-Type header is already modified.

In special cases, when custom headers are required, it is possible to modify
the headers via a PHP-based PSR-15 middleware, or via TypoScript with
"config.additionalHeaders".

.. index:: TypoScript, NotScanned, ext:frontend
