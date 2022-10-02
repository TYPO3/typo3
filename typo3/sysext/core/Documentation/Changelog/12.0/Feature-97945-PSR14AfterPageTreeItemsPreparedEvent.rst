.. include:: /Includes.rst.txt

.. _feature-97945:

========================================================
Feature: #97945 - PSR-14 AfterPageTreeItemsPreparedEvent
========================================================

See :issue:`97945`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent`
has been introduced which allows to modify prepared page tree items. It can also
be used as a replacement for the now removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Workspaces\Service\WorkspaceService']['hasPageRecordVersions']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Workspaces\Service\WorkspaceService']['fetchPagesWithVersionsInTable']`
:doc:`hooks <../12.0/Breaking-97945-RemovedWorkspaceServiceHooks>`.

The event is dispatched in the :php:`TreeController` after the page tree items
have been resolved and prepared. The event provides the current PSR-7 Request
as well as the page tree items. All items contain the corresponding page
record in the special :php:`_page` key.

Example
=======

Registration of the event in your extension's :file:`Services.yaml` file:

..  code-block:: yaml

    MyVendor\MyPackage\Workspaces\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/workspaces/modify-page-tree-items'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;

    final class MyEventListener
    {
        public function __invoke(AfterPageTreeItemsPreparedEvent $event): void
        {
            $items = $event->getItems();
            foreach ($items as $item) {
                // Setting special item for page with id 123
                if ($item['_page']['uid'] === 123) {
                    $item['icon'] = 'my-special-icon';
                }
            }
            $event->setItems($items);
        }
    }

Impact
======

It is now possible to modify the prepared page tree items before they are
returned by the :php:`TreeController`, using the new PSR-14 event
:php:`AfterPageTreeItemsPreparedEvent`.

.. index:: Backend, PHP-API, ext:backend
