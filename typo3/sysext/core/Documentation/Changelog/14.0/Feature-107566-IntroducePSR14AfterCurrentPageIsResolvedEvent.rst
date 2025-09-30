.. include:: /Includes.rst.txt

.. _feature-107566-1759226649:

==============================================================
Feature: #107566 - PSR-14 Event after current page is resolved
==============================================================

See :issue:`107566`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\AfterCurrentPageIsResolvedEvent`
has been introduced which serves as an improved replacement for the now
:ref:`removed <breaking-107566-1759226580>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage']`.

The new event is dispatched after the current page has been resolved.

The event provides the following public properties:

* :php:`$currentPage`: The current page
* :php:`$formRuntime`: The form runtime object (readonly)
* :php:`$lastDisplayedPage`: The last displayed page (readonly)
* :php:`$request`: The current request (readonly)

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\AfterCurrentPageIsResolvedEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/after-current-page-is-resolved-event',
        )]
        public function __invoke(AfterCurrentPageIsResolvedEvent $event): void
        {
            $event->currentPage->setRenderingOption('enabled', false);
        }
    }

Impact
======

With the new PSR-14 :php:`AfterCurrentPageIsResolvedEvent`, it's now
possible manipulate the current page after it has been resolved.

.. index:: Backend, ext:form
