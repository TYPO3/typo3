.. include:: /Includes.rst.txt

.. _breaking-99807-1706107646:

==========================================================
Breaking: #99807 - Relocated ModifyUrlForCanonicalTagEvent
==========================================================

See :issue:`99807`

Description
===========

The :php:`\TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent` has been
:doc:`improved <../13.0/Feature-99807-ImproveModifyUrlForCanonicalTagEvent>`.

Therefore, the event is now always dispatched after the standard functionality
has been executed, such as fetching the URL from the page properties.

The event is furthermore also dispatched in case the canonical tag generation
has been disabled via TypoScript or the page properties. This allows greater
influence in the generation process, but might break existing setups, which
rely on listeners are being called before standard functionality has been
executed or only in case generation is enabled.

Effectively, this also means that :php:`getUrl()` might already return a
non-empty :php:`string`.

Impact
======

The :php:`\TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent` is now always
dispatched when generating canonical tags, just before the final tag markup
is being built.

Affected installations
======================

TYPO3 installations with custom extensions, whose event listeners rely on the
event being dispatched before standard functionality has been executed or
only in case generation has not been disabled.

Migration
=========

Adjust your listeners by respecting the new execution order. Therefore, the
event contains the new :php:`getCanonicalGenerationDisabledException()` method,
which can be used to determine whether generation is disabled and the reason
for it.

.. index:: PHP-API, NotScanned, ext:seo
