.. include:: /Includes.rst.txt

.. _feature-107388-1756971278:

===============================================================
Feature: #107388 - PSR-14 to manipulate form before it is saved
===============================================================

See :issue:`107388`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\BeforeFormIsSavedEvent`
has been introduced which serves as a direct replacement for the now
:ref:`removed <breaking-107388-1756971206>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormSave']`.

The new event is dispatched just right before a form is saved in the backend.

The event provides the following public properties:

* :php:`$form`: The form definition array
* :php:`$formPersistenceIdentifier`: The form persistence identifier (to store the form)

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\BeforeFormIsSavedEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-form-is-saved',
        )]
        public function __invoke(BeforeFormIsSavedEvent $event): void
        {
            $event->form['label'] = 'foo';
        }
    }

Impact
======

With the new :php:`BeforeFormIsSavedEvent`, it's now
possible to modify a form definition as well as the
form persistence identifier before it gets saved.

.. index:: Backend, ext:form
