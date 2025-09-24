.. include:: /Includes.rst.txt

.. _feature-98239-1758890522:

===============================================================
Feature: #98239 - PSR-14 Event to modify form after being built
===============================================================

See :issue:`98239`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent`
has been introduced which serves as an improved replacement for the now
:ref:`removed <breaking-98239-1758890437>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished']`.

The event provides the :php:`$form` public property.

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/after-form-is-built',
        )]
        public function __invoke(AfterFormIsBuiltEvent $event): void
        {
            $event->form->setLabel('foo');
        }
    }

Impact
======

With the new :php:`AfterFormIsBuiltEvent`, it's now
possible to modify the form definition after it has been built.

.. index:: Backend, ext:form
