.. include:: ../../Includes.txt

=================================================================
Feature: #92358 - Add getModuleTemplate() to PageLayoutController
=================================================================

See :issue:`92358`

Description
===========

The :php:`PageLayoutController` features two hooks for manipulating the
Page module. `drawHeaderHook` and `drawFooterHook`. Those hooks already
receive the parent object :php:`PageLayoutController`. Since the calling
code expects the hooks to return additional content, it was previously
not possible to change other parts of the module, e.g. the module header.

To give developers more possibilities in manipulating the page module,
using the mentioned hooks, the parent object now contains a new getter
method :php:`getModuleTemplate()`. It can for example be used to add an
additional button to the modules' button bar.

.. code-block:: php

   public function drawHeaderHook(array $parameters, PageLayoutController $parentObject)
   {
      $moduleTemplate = $parentObject->getModuleTemplate();
      $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

      $linkButton = $buttonBar
         ->makeLinkButton()
         ->setHref('/typo3/some/url')
         ->setTitle('My custom button')
         ->setClasses('custom-link-class')
         ->setIcon($moduleTemplate->getIconFactory()->getIcon('actions-link', Icon::SIZE_SMALL));

      $buttonBar->addButton($linkButton);
   }

Impact
======

When using either the `drawHeaderHook` or the `drawFooterHook` of the
:php:`PageLayoutController`, the provided parent object now contains
the :php:`getModuleTemplate()` method, which can be used to retrieve
the corresponding :php:`ModuleTemplate` instance. This provides
more flexibility to 3rd party code manipulating the page module view.

.. index:: Backend, PHP-API, ext:backend
