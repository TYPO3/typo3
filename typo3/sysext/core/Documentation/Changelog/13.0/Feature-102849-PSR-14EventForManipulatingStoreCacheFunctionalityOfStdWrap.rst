.. include:: /Includes.rst.txt

.. _feature-102849-1705513646:

=====================================================================================
Feature: #102849 - PSR-14 event for manipulating store cache functionality of stdWrap
=====================================================================================

See :issue:`102849`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapContentStoredInCacheEvent`
has been introduced which serves as a more powerful replacement for the now removed
hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore']`.

The event is being dispatched just before the final stdWrap content is added to
the cache and allows to fully manipulate the :php:`$content` to be added, the
cache :php:`$tags` to be used as well as the corresponding cache :php:`$key`
and the cache :php:`$lifetime`. Therefore, listeners can use the public getter
and setter methods.

Additionally, the new event provides the full TypoScript :php:`$configuration`
and the current :php:`$contentObjectRenderer` instance.

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapContentStoredInCacheEvent;

    final class BeforeStdWrapContentStoredInCacheEventListener
    {
        #[AsEventListener]
        public function __invoke(BeforeStdWrapContentStoredInCacheEvent $event): void
        {
            if (in_array('foo', $event->getTags(), true)) {
                $event->setContent('modified-content');
            }
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to fully manipulate the content,
the cache tags as well as further relevant information, used by the caching
functionality of stdWrap.

.. index:: Frontend, PHP-API, ext:frontend
