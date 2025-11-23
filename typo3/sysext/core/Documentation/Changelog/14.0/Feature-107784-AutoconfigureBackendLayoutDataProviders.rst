..  include:: /Includes.rst.txt

..  _feature-107784-1760946896:

==============================================================
Feature: #107784 - Autoconfigure backend layout data providers
==============================================================

See :issue:`107784`

Description
===========

Backend layout providers are now autoconfigured once they implement the required
:php-short:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface`. Each
autoconfigured layout provider is tagged with
`page_layout.data_provider` in the service container and is automatically added
to the global
:php-short:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection`,
if autoconfiguration is enabled in :file:`Services.yaml` or
:file:`Services.php`.

Since backend layout providers must be identifiable to establish a relation to
a configured backend layout, the corresponding interface has been extended.
It now requires backend layout providers to implement a new method
:php:`getIdentifier()`.

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Classes/View/BackendLayout/MyLayoutDataProvider.php

    use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;

    final class MyLayoutDataProvider implements DataProviderInterface
    {
        // ...

        public function getIdentifier(): string
        {
            return 'my_provider';
        }
    }

..  important::
    The identifier returned by :php:`getIdentifier()` must:

    *   Be a non-empty string
    *   Not contain double underscores (`__`)
        (used as a separator for combined identifiers)
    *   Be unique across all registered backend layout data providers

    Example of a combined identifier: :php:`my_provider__my_layout`


Manual service configuration
----------------------------

If autoconfiguration is disabled, manually tag the service in
:file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

     services:
       MyVendor\MyExtension\View\BackendLayout\MyLayoutDataProvider:
         tags:
           - name: page_layout.data_provider


Provider ordering
-----------------

If you need to control the order in which providers are processed, use service
priorities in your :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      MyVendor\MyExtension\View\BackendLayout\MyLayoutDataProvider:
        tags:
          - name: page_layout.data_provider
            priority: 100


Impact
======

Backend layout data providers are now automatically registered and can be used
without further configuration. This improves developer experience and reduces
configuration overhead. The previous registration method via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`
can no longer be used. Instead, existing backend layout providers must
implement the new method :php:`getIdentifier()`.

Using the new autoconfigure-based approach, developers can still support
multiple TYPO3 Core versions by keeping the legacy array-based approach next
to the new autoconfigure-based configuration.

..  index:: Backend, PHP-API, ext:backend
