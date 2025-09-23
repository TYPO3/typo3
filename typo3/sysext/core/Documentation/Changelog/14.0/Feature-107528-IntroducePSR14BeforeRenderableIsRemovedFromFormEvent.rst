.. include:: /Includes.rst.txt

.. _feature-107528-1758703683:

======================================================================
Feature: #107528 - PSR-14 Event before renderable is removed from form
======================================================================

See :issue:`107528`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\BeforeRenderableIsRemovedFromFormEvent`
has been introduced which serves as an improved replacement for the now
:ref:`removed <breaking-107528-1758703683>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable']`.

The new event is dispatched just right before a renderable is deleted from the form.

The event provides the following public properties:

* :php:`$renderable`: The form element (readonly)
* :php:`$preventRemoval`: A boolean flag that can be set to true
  to prevent the removal of the renderable.

The new event is stoppable. As soon as :php:`$preventRemoval` is set to
:php:`true`, no further listener gets called.

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\BeforeRenderableIsRemovedFromFormEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-renderable-is-removed-from-form-event',
        )]
        public function __invoke(BeforeRenderableIsRemovedFromFormEvent $event): void
        {
            $event->preventRemoval = true;
            $renderable = $event->renderable;
            // Do something with the renderable
        }
    }

Impact
======

With the new PSR-14 :php:`BeforeRenderableIsRemovedFromFormEvent`, it's now
possible to prevent the deletion of a renderable and to add custom logic
based on the deletion.

.. index:: Backend, ext:form
