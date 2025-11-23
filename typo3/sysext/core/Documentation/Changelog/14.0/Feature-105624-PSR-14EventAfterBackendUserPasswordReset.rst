..  include:: /Includes.rst.txt

..  _feature-105624-1731956541:

============================================================================
Feature: #105624 - PSR-14 event after a backend user password has been reset
============================================================================

See :issue:`105624`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Authentication\Event\PasswordHasBeenResetEvent`
has been introduced. It is dispatched right after a backend user has reset their password
and it has been hashed and persisted to the database.

The event contains the corresponding backend user UID.

Example
=======

The corresponding event listener class:

..  code-block:: php

    <?php

    namespace Vendor\MyPackage\Backend\EventListener;

    use TYPO3\CMS\Backend\Authentication\Event\PasswordHasBeenResetEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class PasswordHasBeenResetEventListener
    {
        #[AsEventListener('my-package/backend/password-has-been-reset')]
        public function __invoke(PasswordHasBeenResetEvent $event): void
        {
            $userId = $event->userId;
            // Add custom logic for the backend user UID
        }
    }

Impact
======

It is now possible to add custom business logic after a backend user has reset
their password using the new PSR-14 event
:php-short:`\TYPO3\CMS\Backend\Authentication\Event\PasswordHasBeenResetEvent`.

..  index:: Backend, PHP-API, ext:backend
