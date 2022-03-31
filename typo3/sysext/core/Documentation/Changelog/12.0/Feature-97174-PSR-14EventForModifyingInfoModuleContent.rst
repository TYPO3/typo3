.. include:: /Includes.rst.txt

================================================================
Feature: #97174 - PSR-14 Event for modifying info module content
================================================================

See :issue:`97174`

Description
===========

A new PSR-14 Event :php:`\TYPO3\CMS\Info\Controller\Event\ModifyInfoModuleContentEvent`
has been introduced which serves as a more powerful and flexible alternative
for the now removed :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook']`
hook.

While the removed hook effectively only allowed to add content
to the footer of the "Pagetree Overview" submodule in :guilabel:`Web > Info`,
does the new PSR-14 Event now allow to modify the content above and below the
actual info module content. This means the content, added in the event, is
displayed in each submodule of :guilabel:`Web > Info`.

The PSR-14 Event also provides the :php:`getCurrentModule()` method, which
returns the currently requested (sub)module. It's therefore possible to
limit the added content to a subset of the available :guilabel:`Web > Info`
submodules.

Next to the :php:`getRequest()` and the :php:`getModuleTemplate()` methods does
the event also feature the usual getter and setter for the header and footer
content.

Access control
==============

The added content is by defualt always displayed. The PSR-14 Event however
provides the :php:`hasAccess()` method, returning whether the access checks
in the module were passed by the user.

This way, event listeners can decide on their own, whether their content
should always be shown, or only if a user also has access to the main module
content.

Example
=======

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Backend\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/backend/content-to-info-module'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Info\Controller\Event\ModifyInfoModuleContentEvent;

    class MyEventListener {

        public function __invoke(ModifyInfoModuleContentEvent $event): void
        {
            // Add header content for the "Page TSconfig" submodule if user has access to module content
            if ($event->hasAccess() && $event->getCurrentModule()->getIdentifier() === 'web_info_pagets') {
                $event->addHeaderContent('<h3>Additional header content</h3>');
            }
        }
    }

Impact
======

It's now possible to modify the header and footer content of the
:guilabel:`Web > Info` module, using the new PSR-14 event.

.. index:: Backend, PHP-API, ext:info
