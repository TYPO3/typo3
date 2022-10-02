.. include:: /Includes.rst.txt

.. _deprecation-96500:

======================================================
Deprecation: #96500 - ContentObjectRenderer->getMailTo
======================================================

See :issue:`96500`

Description
===========

Since :issue:`96483`, the :html:`<f:link.email/>` ViewHelper is directly
using `TypoLink` for the email link generation. As a result, the
:php:`ContentObjectRenderer->getMailTo()` method was only used in
:php:`EmailLinkBuilder`, the central place for building email links
with `TypoLink`.

To stick to the separation of concerns principle, the corresponding
functionality has been moved to :php:`EmailLinkBuilder->processEmailLink()`
and the :php:`ContentObjectRenderer->getMailTo()` method has been
marked as deprecated.

Impact
======

Calling :php:`ContentObjectRenderer->getMailTo()` will trigger a
PHP :php:`E_USER_DEPRECATED` error. The extension scanner will
find usages as weak match.

Affected Installations
======================

All installations directly calling :php:`ContentObjectRenderer->getMailTo()`
in custom extension code.

Migration
=========

All occurrences in extension code have to be replaced by
:php:`EmailLinkBuilder->processEmailLink()`.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
