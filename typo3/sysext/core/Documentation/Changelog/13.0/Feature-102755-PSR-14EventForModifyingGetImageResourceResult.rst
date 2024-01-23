.. include:: /Includes.rst.txt

.. _feature-102755-1704381990:

=====================================================================
Feature: #102755 - PSR-14 event for modifying getImageResource result
=====================================================================

See :issue:`102755`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterImageResourceResolvedEvent`
has been introduced which serves as a replacement for the now removed
hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImgResource']`.

The event is being dispatched just before :php:`ContentObjectRenderer->getImgResource()`
is about to return the resolved :php:`\TYPO3\CMS\Core\Imaging\ImageResource` DTO.
The event is therefore in comparison to the removed hook always dispatched,
even if no :php:`ImageResource` could be resolved. In this case, the
corresponding return value is :php:`null`.

.. note::

    Instead of an :php:`array` :php:`ContentObjectRenderer` now handles
    the image resource with the new :php:`ImageResource` :abbr:`DTO (Data Transfer Object)`.
    This means, :php:`ContentObjectRenderer->getImgResource()` returns either the new
    DTO or null.

To modify the :php:`getImgResource()` result, the following methods are available:

- :php:`setImageResource()`: Allows to set the :php:`ImageResource` to return
- :php:`getImageResource()`: Returns the resolved :php:`ImageResource` or :php:`null`
- :php:`getFile()`: Returns the :php:`$file`, passed to the :php:`getImageResource` function
- :php:`getFileArray()`: Returns the :php:`$fileArray`, passed to the :php:`getImageResource` function


Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\ContentObject\Event\AfterImageResourceResolvedEvent;

    final class AfterImageResourceResolvedEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterImageResourceResolvedEvent $event): void
        {
            $modifiedImageResource = $event
                ->getImageResource()
                ->withWidth(123);

            $event->setImageResource($modifiedImageResource);
        }
    }

Impact
======

Using the new PSR-14 Event, it's now possible to modify the resolved
:php:`getImageResource()` result.

Additionally, the :php:`ImageResource` DTO allows an improved API as
developers do no longer have to deal with unnamed array keys but benefit
from the object-oriented approach, using corresponding getter and setter.

.. index:: Frontend, PHP-API, ext:frontend
