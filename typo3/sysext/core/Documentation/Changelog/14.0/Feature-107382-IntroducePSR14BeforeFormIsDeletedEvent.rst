..  include:: /Includes.rst.txt

..  _feature-107382-1756901428:

==============================================
Feature: #107382 - PSR-14 before form deletion
==============================================

See :issue:`107382`

Description
===========

A new PSR-14 event :php-short:`\TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent`
has been introduced. It serves as a direct replacement for the now
:ref:`removed <breaking-107382-1756901681>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete']`.

The new event is dispatched immediately before a form is deleted in the
backend.

The event provides the following public properties:

*   :php:`$formPersistenceIdentifier`: The form persistence identifier
    (read-only)
*   :php:`$preventDeletion`: A boolean flag that can be set to `true`
    to prevent the deletion of the form

The new event is stoppable. As soon as :php:`$preventDeletion` is set to
:php:`true`, no further listener is called.

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: Example event listener class

    namespace MyVendor\MyExtension\Form\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent;

    final class BeforeFormIsDeletedEventListener
    {
        #[AsEventListener('my_extension/before-form-is-deleted')]
        public function __invoke(BeforeFormIsDeletedEvent $event): void
        {
            $event->preventDeletion = true;
            $persistenceIdentifier = $event->formPersistenceIdentifier;
            // Do something with the persistence identifier
        }
    }

Impact
======

With the new :php-short:`\TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent`, it is
now possible to prevent the deletion of a form and to add custom logic based on
the delete action.

..  index:: Backend, ext:form
