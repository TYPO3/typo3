.. include:: /Includes.rst.txt

=====================================================
Deprecation: #95164 - ext:backend BackendTemplateView
=====================================================

See :issue:`95164`

Description
===========

To simplify and align the view part of Extbase-based backend module controller code with
non-Extbase based controllers, class :php:`TYPO3\CMS\Backend\View\BackendTemplateView`
has been marked as deprecated and will be removed in TYPO3 v12.

This follows the general Core strategy to have document header related code
using the :php:`ModuleTemplate` class structure within controllers directly, while
Extbase views render only the main body part.


Impact
======

Extensions should switch away from using :php:`BackendTemplateView`. By hiding an
instance of :php:`ModuleTemplate` class, :php:`BackendTemplateView` basically added
a no longer needed level of indirection to code that should be located directly
within controller actions.

Together with the TYPO3 v11 requirement within Extbase controller actions to return
responses directly, combined with Extbase Request object now implementing the PSR-7
ServerRequestInterface, and with the deprecation of other doc header related Fluid
View helpers, Extbase controller action becomes much more obvious code wise.


Affected installations
======================

Instances with extensions using class :php:`BackendTemplateView` are affected.
Candidates are typically Extbase based extensions that deliver backend modules.
The extension scanner will find usages as strong match.


Migration
=========

A transition away from :php:`BackendTemplateView` should be usually pretty straight:
Instead of retrieving a :php:`ModuleTemplate` instance from the view, the
:php:`ModuleTemplateFactory` should be injected and an instance retrieved using
:php:`create()`.

A typical scenario before:

.. code-block:: php

    class MyController extends ActionController
    {
        protected $defaultViewObjectName = BackendTemplateView::class;

        public function myAction(): ResponseInterface
        {
            $this->view->assign('someVar', 'someContent');
            $moduleTemplate = $this->view->getModuleTemplate();
            // Adding title, menus, buttons, etc. using $moduleTemplate ...
            return $this->htmlResponse();
        }
    }

Dropping :php:`BackendTemplateView` leads to code similar to this:

.. code-block:: php

    class MyController extends ActionController
    {
        protected ModuleTemplateFactory $moduleTemplateFactory;

        public function __construct(
            ModuleTemplateFactory $moduleTemplateFactory,
        ) {
            $this->moduleTemplateFactory = $moduleTemplateFactory;
        }

        public function myAction(): ResponseInterface
        {
            $this->view->assign('someVar', 'someContent');
            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            // Adding title, menus, buttons, etc. using $moduleTemplate ...
            $moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($moduleTemplate->renderContent());
        }
    }


.. index:: Backend, Fluid, PHP-API, FullyScanned, ext:backend
