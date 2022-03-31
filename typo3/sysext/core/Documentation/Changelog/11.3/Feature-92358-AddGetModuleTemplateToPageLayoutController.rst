.. include:: /Includes.rst.txt

=================================================================
Feature: #92358 - Add getModuleTemplate() to PageLayoutController
=================================================================

See :issue:`92358`

Description
===========

The :php:`TYPO3\CMS\Backend\Controller\PageLayoutController` features
two hooks for manipulating the "Page" module. :php:`drawHeaderHook` and
:php:`drawFooterHook`. Those hooks already
receive the parent object :php:`PageLayoutController`. Since the calling
code expects the hooks to return additional content, it was previously
not possible to change other parts of the module, for example the module header.

To give developers more possibilities in manipulating the "Page" module,
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

When using either the :php:`drawHeaderHook` or the :php:`drawFooterHook` of the
:php:`PageLayoutController`, the provided parent object now contains
the :php:`getModuleTemplate()` method, which can be used to retrieve
the corresponding :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate` instance.
This provides more flexibility to third party code manipulating the "Page"
module view.

.. index:: Backend, PHP-API, ext:backend
