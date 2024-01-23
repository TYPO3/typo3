.. include:: /Includes.rst.txt

.. _breaking-102835-1705314374:

===============================================================
Breaking: #102835 - Strict typing in final TypoLinkCodecService
===============================================================

See :issue:`102835`

Description
===========

The :php:`\TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService`, used to encode
and decode TypoLinks, has been declared `readonly` and set `final`.
Additionally, the class does now use strict typing and the :php:`decode()`
method's first parameter :php:`$typoLink` is now a type hinted :php:`string`.

This has been done in combination with the introduction of the two new PSR-14
events :php:`BeforeTypoLinkEncodedEvent` and :php:`AfterTypoLinkDecodedEvent`,
which allow to fully influence the encode and decode functionality, making
any cross classing superfluous.

Impact
======

Extending / cross classing :php:`TypoLinkCodecService` does no longer work
and will lead to PHP errors.

Calling :php:`decode()` with the first parameter :php:`$typolink` being not
a :php:`string` will lead to a PHP TypeError.


Affected installations
======================

All installations extending / cross classing :php:`TypoLinkCodecService` or
calling :php:`decode()` with the first parameter :php:`$typolink` not being
a :php:`string`.


Migration
=========

Instead of extending / cross classing :php:`TypoLinkCodecService` use the
:doc:`new PSR-14 events <../13.0/Feature-102835-AddPSR-14EventsToManipulateTypoLinkCodecService>`
to modify the functionality.

Ensure to always provide a :php:`string` as first parameter :php:`$typolink`,
when calling :php:`decode()` in your extension code.

.. index:: PHP-API, NotScanned, ext:core
