.. include:: /Includes.rst.txt

.. _breaking-102755-1704381963:

===========================================================
Breaking: #102755 - Improved getImageResource functionality
===========================================================

See :issue:`102755`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImgResource']`
has been removed in favor of the new PSR-14 event
:php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterImageResourceResolvedEvent`.

The new event is using the new :php:`\TYPO3\CMS\Core\Imaging\ImageResource` :abbr:`DTO (Data Transfer Object)`,
which allows an improved API as developers do no longer have to deal with
unnamed array keys but benefit from the object-oriented approach, using
corresponding getter and setter. Therefore, the return types of the following
methods have been changed to :php:`?ImageResource`:

* :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->gifBuild()`
* :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getImgResource()`

Impact
======

Any registered hook implementation is not executed anymore
in TYPO3 v13.0+.

Calling the mentioned methods do now return either :php:`null` or an instance
of the :php:`ImageResource` DTO.

The new Event is also using the new DTO instead of an array.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook or calling
mentioned methods directly.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v12 (using the hook) and v13+ (using the new event)
when implementing the event as well without any further deprecations.
Use the :doc:`PSR-14 event <../13.0/Feature-102755-PSR-14EventForModifyingGetImageResourceResult>`
to allow greater influence in the functionality.

Additionally, adjust your code to handle the new return types appropriately.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
