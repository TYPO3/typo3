..  include:: /Includes.rst.txt

..  _feature-107528-1758703683:

======================================================================
Feature: #107528 - PSR-14 event before renderable is removed from form
======================================================================

See :issue:`107528`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Event\BeforeRenderableIsRemovedFromFormEvent`
has been introduced. It serves as an improved replacement for the now
:ref:`removed <breaking-107528-1758703683>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable']`.

The new event is dispatched immediately before a renderable is deleted from
the form.

The event provides the following public properties:

*   :php:`$renderable`: The form element (read-only).
*   :php:`$preventRemoval`: A boolean flag that can be set to `true`
    to prevent the removal of the renderable.

The event is stoppable. As soon as :php:`$preventRemoval` is set to
:php:`true`, no further listeners are executed.

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: Example event listener class

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\BeforeRenderableIsRemovedFromFormEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-renderable-is-removed-from-form-event',
        )]
        public function __invoke(BeforeRenderableIsRemovedFromFormEvent $event): void
        {
            $event->preventRemoval = true;
            $renderable = $event->renderable;
            // Custom logic before the renderable is removed
        }
    }

Impact
======

With the new :php-short:`\TYPO3\CMS\Form\Event\BeforeRenderableIsRemovedFromFormEvent`,
it is now possible to prevent the deletion of a renderable and to add custom
logic based on the deletion.

..  index:: Backend, ext:form
