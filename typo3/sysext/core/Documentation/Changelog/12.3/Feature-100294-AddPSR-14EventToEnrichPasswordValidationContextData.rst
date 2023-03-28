.. include:: /Includes.rst.txt

.. _feature-100294-1679766730:

=============================================================================
Feature: #100294 - Add PSR-14 event to enrich password validation ContextData
=============================================================================

See :issue:`100294`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent`
has been added, which allows extension authors to enrich the
:php:`\TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData`
DTO used in password policy validation.

The PSR-14 event is dispatched  in all classes, where a user password is
validated against the globally configured password policy.

The event features the following methods:

- :php:`getContextData()` returns the current :php:`ContextData` DTO
- :php:`getUserData()` returns an array with user data available from the
  initiating class
- :php:`getInitiatingClass()` returns the class name, where the
  :php:`ContextData` DTO is created

The event can be used to enrich the :php:`ContextData` DTO with additional data
used in custom password policy validators.

..  note::

    The user data returned by :php:`getUserData()` will include user data
    available from the initiating class only. Therefore, event listeners should
    always consider the initiating class name when accessing data from
    :php:`getUserData()`. If required user data is not available via
    :php:`getUserData()`, it can possibly be retrieved by a custom database
    query (e.g. data from user table in the password reset process by fetching
    the user with the :php:`uid` given in :php:`getUserData()` array).

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\PasswordPolicy\EventListener\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/enrich-context-data'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/PasswordPolicy/EventListener/MyEventListener.php

    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent;

    final class MyEventListener
    {
        public function __invoke(EnrichPasswordValidationContextDataEvent $event): void
        {
            if ($event->getInitiatingClass() === DataHandler::class) {
                $event->getContextData()->setData('currentMiddleName', $event->getUserData()['middle_name'] ?? '');
                $event->getContextData()->setData('currentEmail', $event->getUserData()['email'] ?? '');
            }
        }
    }


Impact
======

With the new :php:`EnrichPasswordValidationContextDataEvent`, it is now
possible to enrich the :php:`ContextData` DTO used in password policy
validation with additional data.

.. index:: ext:core
