..  include:: /Includes.rst.txt

..  _feature-107380-1756896691:

================================================================
Feature: #107380 - PSR-14 to manipulate form duplication process
================================================================

See :issue:`107380`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Event\BeforeFormIsDuplicatedEvent`
has been introduced. It serves as a direct replacement for the now
:ref:`removed <breaking-107380-1756896552>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate']`.

The new event is dispatched immediately before a form is duplicated in the
backend.

The event provides the following public properties:

*   :php:`$form`: The form definition array
*   :php:`$formPersistenceIdentifier`: The form persistence identifier used to
    store the duplicated form

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: Example event listener class

    namespace MyVendor\MyExtension\Form\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\BeforeFormIsDuplicatedEvent;

    final class BeforeFormIsDuplicatedEventListener
    {
        #[AsEventListener('my_extension/before-form-is-duplicated')]
        public function __invoke(BeforeFormIsDuplicatedEvent $event): void
        {
            $event->form['label'] = 'foo';
        }
    }

Impact
======

With the new :php-short:`\TYPO3\CMS\Form\Event\BeforeFormIsDuplicatedEvent`, it
is now possible to modify a form before it is duplicated.

..  index:: Backend, ext:form
