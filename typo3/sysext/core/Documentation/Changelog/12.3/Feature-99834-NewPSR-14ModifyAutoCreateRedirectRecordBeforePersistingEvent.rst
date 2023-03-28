.. include:: /Includes.rst.txt

.. _feature-99834-1675612872:

================================================================================
Feature: #99834 - New PSR-14 ModifyAutoCreateRedirectRecordBeforePersistingEvent
================================================================================

See :issue:`99834`

Description
===========

A new PSR-14 :php:`\TYPO3\CMS\Redirects\Event\ModifyAutoCreateRedirectRecordBeforePersistingEvent`
is introduced, allowing extension authors to modify the redirect record before it is persisted to
the database. This can be used to change values based on circumstances, for example, like
different sub tree settings, not covered by the Core site configuration. Another use-case
could be to write data to additional :sql:`sys_redirect` columns added by a custom
extension for later use.

..  note::

    To handle later updates or react on manually created redirects in the backend
    module, available hooks of :php:`\TYPO3\CMS\Core\DataHandling\DataHandler`
    can be used.

Example:
--------

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Redirects\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/modify-auto-create-redirect-record-before-persisting'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Redirects/MyEventListener.php

    namespace MyVendor\MyExtension\Redirects;

    use TYPO3\CMS\Redirects\Event\ModifyAutoCreateRedirectRecordBeforePersistingEvent;
    use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;

    final class MyEventListener {

        public function __invoke(
            ModifyAutoCreateRedirectRecordBeforePersistingEvent $event
        ): void {

            // only work on plain slug replacement redirect sources.
            if (!($event->getSource() instanceof PlainSlugReplacementRedirectSource)) {
                return;
            }

            // Get prepared redirect record and change some values
            $record = $event->getRedirectRecord();

            // override the status code, eventually to another value than
            // configured in the site configuration
            $record['status_code'] = 307;

            // Set value to a field extended by a custom extension, to persist
            // additional data to the redirect record.
            $record['custom_field_added_by_a_extension']
                = 'page_' . $event->getSlugRedirectChangeItem()->getPageId();

            // Update changed record in event to ensure changed values are saved.
            $event->setRedirectRecord($record);
        }
    }


Impact
======

With the new :php:`ModifyAutoCreateRedirectRecordBeforePersistingEvent`, it is now
possible to modify the auto-create redirect record before it is persisted to the database.
Manually created redirects or updated redirects can be handled by using the well-known
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler` and the available hooks.

.. index:: PHP-API, ext:redirects
