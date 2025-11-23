..  include:: /Includes.rst.txt

..  _feature-107566-1759226649:

==============================================================
Feature: #107566 - PSR-14 event after current page is resolved
==============================================================

See :issue:`107566`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Event\AfterCurrentPageIsResolvedEvent`
has been introduced. It serves as an improved replacement for the now
:ref:`removed <breaking-107566-1759226580>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage']`.

The new event is dispatched after the current page has been resolved.

The event provides the following public properties:

*   :php:`$currentPage`: The current page.
*   :php:`$formRuntime`: The form runtime object (read-only).
*   :php:`$lastDisplayedPage`: The last displayed page (read-only).
*   :php:`$request`: The current request (read-only).

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: Example event listener class

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\AfterCurrentPageIsResolvedEvent;

    final class AfterCurrentPageIsResolvedEventListener
    {
        #[AsEventListener('my-extension/after-current-page-is-resolved-event')]
        public function __invoke(AfterCurrentPageIsResolvedEvent $event): void
        {
            $event->currentPage->setRenderingOption('enabled', false);
        }
    }

Impact
======

With the new :php-short:`\TYPO3\CMS\Form\Event\AfterCurrentPageIsResolvedEvent`,
it is now possible to manipulate the current page after it has been resolved.

..  index:: Backend, ext:form
