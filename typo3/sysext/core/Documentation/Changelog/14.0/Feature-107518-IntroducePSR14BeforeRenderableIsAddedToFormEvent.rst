..  include:: /Includes.rst.txt

..  _feature-107518-1758539757:

=========================================================================
Feature: #107518 - PSR-14 event to modify form elements after being added
=========================================================================

See :issue:`107518`

Description
===========

A new PSR-14 event :php-short:`\TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent`
has been introduced. It serves as an improved replacement for the now
:ref:`removed <breaking-107518-1758539663>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement']`.

The new event is dispatched immediately before a renderable has been
constructed and added to the corresponding form. This allows full
customization of the renderable after it has been initialized.

The event provides the :php:`$renderable` public property.

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: Example event listener class

    namespace MyVendor\MyExtension\Form\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent;

    final class BeforeRenderableIsAddedToFormEventListener
    {
        #[AsEventListener('my-extension/before-renderable-is-added-to-form-event')]
        public function __invoke(BeforeRenderableIsAddedToFormEvent $event): void
        {
            $event->renderable->setLabel('foo');
        }
    }

Impact
======

With the new :php-short:`\TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent`,
it is now possible to modify a renderable after it has been initialized and
right before being added to its form.

..  index:: Backend, ext:form
