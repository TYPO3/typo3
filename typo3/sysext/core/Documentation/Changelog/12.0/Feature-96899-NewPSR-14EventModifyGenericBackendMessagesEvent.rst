.. include:: ../../Includes.txt

=====================================================================
Feature: #96899 - New PSR-14 Event: ModifyGenericBackendMessagesEvent
=====================================================================

See :issue:`96899`

Description
===========

A new PSR-14 Event :php:`\TYPO3\CMS\Backend\Controller\Event\ModifyGenericBackendMessagesEvent`
has been introduced. It serves as direct replacement for the now removed hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages']`.

Example
=======

Registration of an event listener in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Backend\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/backend/add-message'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\ModifyGenericBackendMessagesEvent;
    use TYPO3\CMS\Core\Messaging\FlashMessage;

    class MyEventListener {

        public function __invoke(ModifyGenericBackendMessagesEvent $event): void
        {
            // Add a custom message
            $event->addMessage(new FlashMessage('My custom message'));
        }
    }

Impact
======

The PSR-14 Event allows to add or alter messages that are displayed
in the "About" module (default start module of the TYPO3 Backend).

Extensions such as "Reports" already use this Event to display custom
messages based on the status of the system.

.. index:: Backend, PHP-API, ext:backend
