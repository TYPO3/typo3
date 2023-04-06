.. include:: /Includes.rst.txt

.. _feature-100278-1679604666:

================================================================================
Feature: #100278 - PSR-14 Event after failed logins in backend or frontend users
================================================================================

See :issue:`100278`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Authentication\Event\LoginAttemptFailedEvent`
has been introduced. The event allows to notify remote systems about failed logins.

The event features the following methods:

-   :php:`isFrontendAttempt()`: Whether this was a login attempt from a frontend login form
-   :php:`isBackendAttempt()`: Whether this was a login attempt in the backend
-   :php:`getUser()`: Returns the :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication` derivative in question
-   :php:`getRequest()`: Returns the current PSR-7 request object
-   :php:`getLoginData()`: The attempted login data without sensitive information

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Authentication\EventListener\MyEventListener:
        tags:
          - name: event.listener
            identifier: 'my-extension/login-attempt-failed'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Authentication/EventListener/MyEventListener.php

    namespace MyVendor\MyExtension\Authentication\EventListener;

    use TYPO3\CMS\Core\Authentication\Event\LoginAttemptFailedEvent;

    final class MyEventListener
    {
        public function __invoke(LoginAttemptFailedEvent $event): void
        {
            if ($event->getRequest()->getAttribute('normalizedParams')->getRemoteAddress() !== '198.51.100.42') {
                // send an email because an external user login attempt failed
            }
        }
    }


Impact
======

It is now possible to notify external loggers about failed login attempts
while having the full request.

.. index:: Backend, Frontend, PHP-API, ext:core
