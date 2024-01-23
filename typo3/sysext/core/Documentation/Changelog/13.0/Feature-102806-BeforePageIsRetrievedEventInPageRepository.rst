.. include:: /Includes.rst.txt

.. _feature-102806-1704878293:

===============================================================
Feature: #102806 - BeforePageIsRetrievedEvent in PageRepository
===============================================================

See :issue:`102806`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Domain\Event\BeforePageIsRetrievedEvent`
has been introduced, which serves as a more powerful replacement of the removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage']`
hook.

The new event therefore allows to modify the resolving of page records within
:php:`\TYPO3\CMS\Core\Domain\PageRepository->getPage()`.

Impact
======

The event can be used to alter the incoming page ID or to even fetch a fully
loaded page object before the default TYPO3 behaviour is executed, effectively
bypassing the default page resolving.

To modify the incoming parameters, the following methods are available:

- :php:`setPageId()`: Allows to set the :php:`$uid` of a page to resolve
- :php:`setPage()`: Allows to set a :php:`Page` object which bypasses TYPO3 Core functionality


Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Domain\Event\BeforePageIsRetrievedEvent;

    final class CustomPageResolverEventListener
    {
        #[AsEventListener]
        public function __invoke(BeforePageIsRetrievedEvent $event): void
        {
            if ($event->getPageId() === 13) {
                $event->setPageId(42);
            } elseif ($event->getContext()->getPropertyFromAspect('language', 'id') > 0) {
                $event->setPage(new \TYPO3\CMS\Core\Domain\Page(['uid' => 43]));
            }
        }
    }


Impact
======

Using the new PSR-14 event, it's now possible to fully customize the page
resolving in TYPO3's Core API class :php:`\TYPO3\CMS\Core\Domain\PageRepository`.

.. index:: PHP-API, ext:core
