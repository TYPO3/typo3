.. include:: /Includes.rst.txt

.. _feature-97595-1652121042:

=========================================================
Feature: #97595 - Provide default queue for notifications
=========================================================

See :issue:`97595`

Description
===========

To allow dispatching notifications to the user the easy way, a new global flash
message queue, identified by
:php:`TYPO3\CMS\Core\Messaging\FlashMessageQueue::NOTIFICATION_QUEUE`, is
introduced that takes the flash message and renders it as a notification on the
top-right edge of the backend.

Backend modules based on :php:`TYPO3\CMS\Backend\Template\ModuleTemplate`
automatically gain advantage of this feature.

Example
=======

..  code-block:: php

    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
    $notificationQueue = $flashMessageService->getMessageQueueByIdentifier(
        FlashMessageQueue::NOTIFICATION_QUEUE
    );
    $flashMessage = GeneralUtility::makeInstance(
        FlashMessage::class,
        'I\'m a message rendered as notification',
        'Hooray!',
        FlashMessage::OK
    );
    $notificationQueue->enqueue($flashMessage);

Impact
======

All flash messages dispatched to the flash message queue
:php:`FlashMessageQueue::NOTIFICATION_QUEUE` will be rendered as notifications
in the browser.

.. index:: Backend, ext:backend
