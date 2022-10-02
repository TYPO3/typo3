.. include:: /Includes.rst.txt

.. _feature-96526:

================================================================
Feature: #96526 - PSR-14 event for modifying page module content
================================================================

See :issue:`96526`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent`
has been introduced which serves as a more powerful and flexible alternative
for the now removed hooks :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook']`.

Next to the :php:`getRequest()` and :php:`getModuleTemplate()` methods does
the event feature the usual getter and setter for the header and footer
content. It is therefore now possible to not just add additional content to
the module, but to also overwrite existing content or to reorder the content.

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/backend/modify-page-module-content'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;

    class MyEventListener {

        public function __invoke(ModifyPageLayoutContentEvent $event): void
        {
            $event->addHeaderContent('Additional header content');

            $event->setFooterContent('Overwrite footer content');
        }
    }

In contrast to the removed hooks, the new event does not provide the
:php:`PageLayoutController` as :php:`$parentObject`, since :php:`getModuleTemplate()`
has been the only public method, which is now directly included in the event.

Additionally, there were three public properties :php:`$id`, :php:`$pageInfo`
and :php:`$MOD_SETTINGS`, which however had already been marked as :php:`@internal`
in TYPO3 v9. If needed, the information can be retrieved from the request directly.

An example to get the current :php:`$id`:

..  code-block:: php

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $id = (int)($event->getRequest()->getQueryParams()['id'] ?? 0);
    }

Impact
======

The new PSR-14 event allows to modify the content of the page module
header and footer sections in an efficient and flexible way.

.. index:: Backend, PHP-API, ext:backend
