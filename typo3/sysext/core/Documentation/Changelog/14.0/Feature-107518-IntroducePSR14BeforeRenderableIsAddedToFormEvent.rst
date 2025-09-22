.. include:: /Includes.rst.txt

.. _feature-107518-1758539757:

=========================================================================
Feature: #107518 - PSR-14 Event to modify form elements after being added
=========================================================================

See :issue:`107518`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent`
has been introduced which serves as an imrpoved replacement for the now
:ref:`removed <breaking-107518-1758539663>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement']`.

The new event is dispatched right before a renderable has been constructed and
is added to the corresponding form. This therefore allows to fully customize
the renderbale after everything has been initialized.

The event provides the :php:`$renderable` public property.

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-renderable-is-added-to-form-event',
        )]
        public function __invoke(BeforeRenderableIsAddedToFormEvent $event): void
        {
            $event->renderable->setLabel('foo');
        }
    }

Impact
======

With the new :php:`BeforeRenderableIsAddedToFormEvent`, it's now
possible to modify a renderable after it has been initialized and
right before being added to its form.

.. index:: Backend, ext:form
