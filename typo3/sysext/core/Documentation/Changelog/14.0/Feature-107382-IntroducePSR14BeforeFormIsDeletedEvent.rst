.. include:: /Includes.rst.txt

.. _feature-107382-1756901428:

==============================================
Feature: #107382 - PSR-14 before form deletion
==============================================

See :issue:`107382`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent`
has been introduced which serves as a direct replacement for the now
:ref:`removed <breaking-107382-1756901681>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete']`.

The new event is dispatched just right before a form is deleted in the
backend.

The event provides the following public properties:

* :php:`$formPersistenceIdentifier`: The form persistence identifier (readonly)
* :php:`$preventDeletion`: A boolean flag that can be set to true
  to prevent the deletion of the form.

The new event is stoppable. As soon as :php:`$preventDeletion` is set to
:php:`true`, no further listener gets called.

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-form-is-deleted',
        )]
        public function __invoke(BeforeFormIsDeletedEvent $event): void
        {
            $event->preventDeletion = true;
            $persistenceIdentifier = $event->formPersistenceIdentifier;
            // Do something with the persistence identifier
        }
    }

Impact
======

With the new :php:`BeforeFormIsDeletedEvent`, it's now possible to prevent
the deletion of a form and to add custom logic based on the delete action.

.. index:: Backend, ext:form
