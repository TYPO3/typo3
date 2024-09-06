.. include:: /Includes.rst.txt

.. _feature-99807-1706107691:

=======================================================
Feature: #99807 - Improve ModifyUrlForCanonicalTagEvent
=======================================================

See :issue:`99807`

Description
===========

The :php:`\TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent`, used by listeners
to manipulate the URL of the `canonical` tag, has been improved. The event is
now being dispatched after the standard functionality, such as fetching the
URL from the page properties, has been executed.

Additionally, the event is now even dispatched, in case the canonical tag
generation is disabled via TypoScript :typoscript:`disableCanonical` or via
page properties :php:`no_index`. If disabled, the new
:php:`\TYPO3\CMS\Seo\Exception\CanonicalGenerationDisabledException` is being
thrown in the :php:`CanonicalGenerator`. The exception is caught and transferred
to the event, allowing listeners to determine whether generation is disabled,
using the new :php:`getCanonicalGenerationDisabledException()` method, which
either returns the exception with the corresponding reason or :php:`null`.

Impact
======

By relocating and extending the :php:`ModifyUrlForCanonicalTagEvent`,
listeners are now able to fully manipulate the canonical tag generation, even
if the generation is disabled and after the standard functionality has been
executed.

.. index:: PHP-API, ext:seo
