.. include:: /Includes.rst.txt

.. _feature-107569-1759906422:

=============================================================
Feature: #107569 - PSR-14 Event before renderable is rendered
=============================================================

See :issue:`107569`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent`
has been introduced which serves as an replacement for the now
:ref:`removed <breaking-107569-1759906416>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering']`.

The new event is dispatched just right before a renderable is rendered.

The event provides the following public properties:

* :php:`$renderable`: The form element
* :php:`$formRuntime`: The form runtime

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-renderable-is-rendered',
        )]
        public function __invoke(BeforeRenderableIsRenderedEvent $event): void
        {
            $renderable = $event->renderable;
            if ($renderable->getType() !== 'Date') {
                return;
            }
            $date = $event->formRuntime[$renderable->getIdentifier()];
            if ($date instanceof \DateTime) {
                $event->formRuntime[$renderable->getIdentifier()] = $date->format('Y-m-d');
            }
        }
    }

Impact
======

With the new PSR-14 :php:`BeforeRenderableIsRenderedEvent`, it's now
possible to modify the renderable before it's rendered or modify the form runtime.

.. index:: Backend, ext:form
