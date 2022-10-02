.. include:: /Includes.rst.txt

.. _feature-96975:

==================================================================
Feature: #96975 - New PSR-14 events for SiteConfiguration Handling
==================================================================

See :issue:`96975`

Description
===========

Two new events have been added to TYPO3, to allow manipulation of the loaded
SiteConfiguration before it is cached and before the configuration is written to disk.

- :php:`\TYPO3\CMS\Core\Configuration\Event\SiteConfigurationBeforeWriteEvent`
- :php:`\TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent`

Impact
======

The events SiteConfigurationLoadedEvent and SiteConfigurationBeforeWriteEvent
have been introduced.

Both contain the following methods:

- :php:`getSiteIdentifier()`: returns the sites' identifier
- :php:`getConfiguration()`: returns the configuration (loaded or to be written)
- :php:`setConfiguration(array $configuration)`: allows overwriting of the configuration

They allow modification of the site configuration array both before loading and
before writing the configuration to disk.

To register an event listener for the new events, use the following code in your
:file:`Services.yaml`:

..  code-block:: yaml

    services:
      MyCompany\MyPackage\EventListener\SiteConfigurationLoadedListener:
        tags:
          - name: event.listener
            identifier: 'myLoadedListener'
      MyCompany\MyPackage\EventListener\SiteConfigurationBeforeWriteListener:
        tags:
          - name: event.listener
            identifier: 'myWriteListener'

.. index:: PHP-API, ext:core
