.. include:: /Includes.rst.txt

.. _breaking-96501:

==================================================================
Breaking: #96501 - prefixLocalAnchors option in HTMLParser removed
==================================================================

See :issue:`96501`

Description
===========

The property :php:`prefixLocalAnchors` in TypoScript's HTMLParser
is removed without substitution.

This is a leftover from times before there was Site Handling
and absolute URLs, related to :typoscript:`config.prefixLocalAnchors` which was
removed in TYPO3 v8.

The option has many side-effects such as relying on the request
when parsing HTML (which behaves differently in TYPO3 Backend
and in Frontend).

Impact
======

Setting this TypoScript option has no effect anymore.

Affected Installations
======================

TYPO3 installation having TypoScript configured with this
option activated.

Migration
=========

None.

.. index:: Frontend, TypoScript, NotScanned, ext:frontend
