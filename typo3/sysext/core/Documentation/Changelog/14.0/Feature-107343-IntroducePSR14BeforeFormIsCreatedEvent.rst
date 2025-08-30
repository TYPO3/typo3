.. include:: /Includes.rst.txt

.. _feature-107343-1756389242:

============================================================
Feature: #107343 - PSR-14 to manipulate form creation process
============================================================

See :issue:`107343`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\BeforeFormIsCreatedEvent`
has been introduced which serves as a direct replacement for the now
:ref:`removed <breaking-107343-1756559538>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate']`.

The new event is dispatched just right before a new form is created in the
backend.

The event provides the following public properties:

* :php:`$form`: The form definition array
* :php:`$formPersistenceIdentifier`: The form persistence identifier (to store the new form)

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\BeforeFormIsCreatedEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-form-is-created',
        )]
        public function __invoke(BeforeFormIsCreatedEvent $event): void
        {
            $event->form['label'] = 'foo';
        }
    }

Impact
======

With the new :php:`BeforeFormIsCreatedEvent`, it's now
possible to modify a form before it gets created.

.. index:: Backend, ext:form
