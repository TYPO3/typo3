.. include:: /Includes.rst.txt

.. _feature-96806:

=======================================================
Feature: #96806 - PSR-14 event for modifying button bar
=======================================================

See :issue:`96806`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent`
has been introduced. It serves as a direct replacement for the now removed hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']`.

It can be used to modify the button bar in the TYPO3 backend module docheader.

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Frontend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/frontend/modify-button-bar'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;

    class MyEventListener {

        public function __invoke(ModifyButtonBarEvent $event): void
        {
            // Do your magic here
        }
    }

Impact
======

It's now possible to modify the TYPO3 backend button bar, using the
new PSR-14 event.

.. index:: Backend, PHP-API, ext:backend
