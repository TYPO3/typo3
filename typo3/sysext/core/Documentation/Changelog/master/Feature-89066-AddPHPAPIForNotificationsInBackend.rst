.. include:: ../../Includes.txt

==========================================================
Feature: #89066 - Add PHP API for Notifications in backend
==========================================================

See :issue:`89066`

Description
===========
It is now possible to create backend JavaScript notifications with a new PHP API.
The new API provides a simple way to create JavaScript backend notifications (not FlashMessages).

The :php:`NotificationService` is responsible for generating notifications (not FlashMessages)
in the backend. This PHP API provides methods to create JavaScript notifications popups in the
top right corner of the TYPO3 backend.
The scope of this API is backend only! If you need something similar for the frontend or in CLI
context, the FlashMessage API is your friend or you have to implement your own logic.

Examples
--------

A simple notification of type notice:

.. code-block:: php

      GeneralUtility::makeInstance(NotificationService::class)
         ->notice('Notice', 'notice');

A notification of type warning with two buttons:

.. code-block:: php

      GeneralUtility::makeInstance(NotificationService::class)
         ->warning('Warning', 'Are you sure you want delete this record?', 0, [
            new Action('Yes', '... some JS code here ...'),
            new Action('No', '... some JS code here ...', Action::TYPE_DEFERRED),
         ]);

.. index:: Backend, JavaScript, PHP-API, ext:backend
