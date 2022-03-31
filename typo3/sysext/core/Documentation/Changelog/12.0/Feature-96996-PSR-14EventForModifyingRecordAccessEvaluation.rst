.. include:: /Includes.rst.txt

=====================================================================
Feature: #96996 - PSR-14 Event for modifying record access evaluation
=====================================================================

See :issue:`96996`

Description
===========

A new PSR-14 event :php:`RecordAccessGrantedEvent` has been added. It serves
as replacement for the now removed hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']`.

The new PSR-14 event can be used to either define whether record access is granted
for a user, or to even modify the record in question. In case the `$accessGranted`
property is set (either :php:`true` or :php:`false`), the defined settings is
directly used, skipping any further event listener as well as any further
evaluation.

Example
=======

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/set-access-granted'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Core\Domain\Access\RecordAccessGrantedEvent;

    class MyEventListener {

        public function __invoke(RecordAccessGrantedEvent $event): void
        {
            // Manually set access granted
            if ($event->getTable() === 'my_table' && ($event->getRecord()['custom_access_field'] ?? false)) {
                $event->setAccessGranted(true);
            }

            // Update the record to be checked
            $record = $event->getRecord();
            $record['some_field'] = true;
            $event->updateRecord($record);
        }
    }

Impact
======

With the new PSR-14 :php:`RecordAccessGrantedEvent`, it's
now possible to manipulate the record access evaluation by
either directly granting access or by modifying the record
to be evaluated.

.. index:: Frontend, PHP-API, ext:frontend
