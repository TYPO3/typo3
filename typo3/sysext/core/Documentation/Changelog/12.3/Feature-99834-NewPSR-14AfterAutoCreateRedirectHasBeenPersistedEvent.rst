.. include:: /Includes.rst.txt

.. _feature-99834-1675612921:

=========================================================================
Feature: #99834 - New PSR-14 AfterAutoCreateRedirectHasBeenPersistedEvent
=========================================================================

See :issue:`99834`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Redirects\Event\AfterAutoCreateRedirectHasBeenPersistedEvent`
is introduced, allowing extension authors to react on persisted auto-created redirects. This
can be used to call external API or do other tasks based on the real persisted redirects.

..  note::

    To handle later updates or react on manual created redirects in the backend
    module, available hooks of :php:`\TYPO3\CMS\Core\DataHandling\DataHandler`
    can be used.

Example:
--------

Registration of the event listener:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Redirects\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/after-auto-create-redirect-has-been-persisted'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Redirects/MyEventListener.php

    namespace MyVendor\MyExtension\Redirects;

    use TYPO3\CMS\Redirects\Event\AfterAutoCreateRedirectHasBeenPersistedEvent;
    use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;

    class MyEventListener {

        public function __invoke(
            AfterAutoCreateRedirectHasBeenPersistedEvent $event
        ): void {
            $redirectUid = $event->getRedirectRecord()['uid'] ?? null;
            if ($redirectUid === null
                && !($event->getSource() instanceof PlainSlugReplacementRedirectSource)
            ) {
                return;
            }

            // Implement code what should be done with this information. E.g.
            // write to another table, call a rest api or similar. Find your
            // use-case.
        }
    }


Impact
======

With the new :php:`AfterAutoCreateRedirectHasBeenPersistedEvent`, it is now possible
to react on persisted auto-created redirects. Manually created redirects can be handled
by using one of the available :php:`\TYPO3\CMS\Core\DataHandling\DataHandler` hooks,
not suitable for auto-created redirects.

.. index:: PHP-API, ext:redirects
