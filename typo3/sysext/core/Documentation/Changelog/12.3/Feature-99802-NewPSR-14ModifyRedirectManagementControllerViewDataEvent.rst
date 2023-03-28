.. include:: /Includes.rst.txt

.. _feature-99802-1675370033:

============================================================================
Feature: #99802 - New PSR-14 ModifyRedirectManagementControllerViewDataEvent
============================================================================

See :issue:`99802`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent`
is introduced, allowing extension authors to modify or enrich view data for the
:php:`\TYPO3\CMS\Redirects\Controller\ManagementController`. This allows to
display more or other information along the way.

This event features the following methods:

- :php:`getDemand()`: Return the demand object used to retrieve the redirects
- :php:`getRedirects()`: Return the retrieved redirects
- :php:`setRedirects()`: Can be used to set the redirects, for example, after enriching redirect fields
- :php:`getRequest()`: Return the current request
- :php:`getHosts()`: Returns the hosts to be used for the host filter select-box
- :php:`setHosts()`: Can be used to update which hosts are available in the filter select-box
- :php:`getStatusCodes()`: Returns the status codes for the filter select box
- :php:`setStatusCodes()`: Can be used to update which status codes are available in the filter select-box
- :php:`getCreationTypes()`: Returns creation types for the filter select box
- :php:`setCreationTypes()`: Can be used to update which creation types are available in the filter select-box
- :php:`getShowHitCounter()`: Returns if hit counter should be displayed
- :php:`setShowHitCounter()`: Can be used to manage if the hit counter should be displayed
- :php:`getView()`: Returns the current view object, without controller data assigned yet
- :php:`setView()`: Can be used to assign additional data to the view

For example, this event can be used to add additional information to current page records.

Therefore, it can be used to generate custom data, directly assigning to the view.
With overriding the backend view template via page TSconfig this custom data can
be displayed where it is needed, and rendered the way it is wanted.


Example:
--------

Registration of the event listener:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Redirects\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/modify-redirect-management-controller-view-data'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Redirects/MyEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Redirects;

    use TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent;

    final class MyEventListener {

        public function __invoke(
            ModifyRedirectManagementControllerViewDataEvent $event
        ): void {
            $hosts = $event->getHosts();

            // remove wildcard host from list
            $hosts = array_filter($hosts, static fn ($host) => $host['name'] !== '*');

            // update changed hosts list
            $event->setHosts($hosts);
        }
    }


Impact
======

With the new :php:`ModifyRedirectManagementControllerViewDataEvent`, it is
now possible to modify view data or inject further data to the view for the
management view of redirects.

.. index:: PHP-API, ext:redirects
