.. include:: /Includes.rst.txt

.. _feature-100307-1679924551:

========================================================
Feature: #100307 - PSR-14 events for user login & logout
========================================================

See :issue:`100307`

Description
===========

Three new PSR-14 events have been added:

-   :php:`\TYPO3\CMS\Core\Authentication\Event\BeforeUserLogoutEvent`
-   :php:`\TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedOutEvent`
-   :php:`\TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent`

The purpose of these events is to trigger any kind of action when a user
has been successfully logged in or logged out.

TYPO3 Core itself uses :php:`AfterUserLoggedInEvent` in the TYPO3 backend
to send an email to a user, if the login was successful.

The event features the following methods:

-   :php:`getUser()`: Returns the :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication` derivative in question

The PSR-14 event :php:`BeforeUserLogoutEvent` on top has the possibility
to bypass the regular logout process by TYPO3 (removing the cookie and
the user session) by calling :php:`$event->disableRegularLogoutProcess()`
in an event listener.

The PSR-14 event :php:`AfterUserLoggedInEvent` contains the method
:php:`getRequest()` to return PSR-7 request object of the current request.

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Authentication\EventListener\MyEventListener:
        tags:
          - name: event.listener
            identifier: 'my-extension/after-user-logged-in'

The corresponding event listener class for :php:`AfterUserLoggedInEvent`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Authentication/EventListener/MyEventListener.php

    namespace MyVendor\MyExtension\Authentication\EventListener;

    use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent;

    final class MyEventListener
    {
        public function __invoke(AfterUserLoggedInEvent $event): void
        {
            if (
                $event->getUser() instanceof BackendUserAuthentication
                && $event->getUser()->isAdmin()
            )
            {
                // Do something like: Clear all caches after login
            }
        }
    }


Impact
======

It is now possible to modify and adapt user functionality based on successful
login or active logout.

.. index:: Backend, Frontend, PHP-API, ext:core
