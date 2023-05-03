.. include:: /Includes.rst.txt

.. _feature-96641:

======================================================
Feature: #96641 - New PSR-14 event for modifying links
======================================================

See :issue:`96641`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent`
is added which allows PHP developers to modify any kind of link generated
by TYPO3's mighty "typolink()" functionality.

This PSR-14 event also supersedes the :php:`UrlProcessorInterface` logic
which allowed to modify mail URNs or external URLs, but not the
full anchor tag.

In addition, this PSR-14 event also replaces the
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']`
hook which was not executed at all times, and had a cumbersome API
to modify values.

It is also recommended to use the PSR-14 event instead of the global
getATagParams hook (:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getATagParamsPostProc']`)
to add additional attributes (see example below) to links.

All mentioned hooks have been :doc:`removed <../12.0/Breaking-96641-TypoLinkRelatedHooksRemoved>`.

Impact
======

By using the PSR-14 event, it is possible to add attributes to links to
internal pages, or links to files, as the event contains the actual information
of the link type with it.

As the PSR-14 event works with the :php:`LinkResultInterface` object it is possible
to modify or replace the LinkResult information instead of working with string
replacement functionality for adding, changing or removing attributes.

To register an event listener to the new event, use the following code in your
:file:`Services.yaml`:

..  code-block:: yaml

    services:
      MyCompany\MyPackage\TypoLink\LinkModifier:
        tags:
          - name: event.listener
            identifier: 'myLoadedListener'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;

    final class LinkModifier
    {
        public function __invoke(AfterLinkIsGeneratedEvent $event): void
        {
            $linkResult = $event->getLinkResult()->withAttribute('data-enable-lightbox', 'true');
            $event->setLinkResult($linkResult);
        }
    }

.. index:: Frontend, PHP-API, ext:frontend
