.. include:: /Includes.rst.txt

=================================================================
Deprecation: #95235 - Public getter of services in ModuleTemplate
=================================================================

See :issue:`95235`

Description
===========

The public methods :php:`getIconFactory` and :php:`getPageRenderer`
in :php:`TYPO3\CMS\Backend\Template\ModuleTemplate` have been marked as deprecated,
since using this getters only hides the dependencies to those services.

Impact
======

Calling either :php:`getIconFactory` or :php:`getPageRenderer` will
trigger a PHP :php:`E_USER_DEPRECATED` error. The extension scanner also detects
such calls as weak match.

Affected Installations
======================

All installations calling the methods in custom extension code.

Migration
=========

Inject the corresponding services :php:`TYPO3\CMS\Core\Imaging\IconFactory`
and :php:`TYPO3\CMS\Core\Page\PageRenderer` directly in your class.

A current Extbase backend controller might look like:

.. code-block:: php

    class MyController extends ActionController
    {
        protected ModuleTemplateFactory $moduleTemplateFactory;

        public function __construct(ModuleTemplateFactory $moduleTemplateFactory)
        {
            $this->moduleTemplateFactory = $moduleTemplateFactory;
        }

        public function myAction(): ResponseInterface
        {
            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $moduleTemplate->getPageRenderer()->loadRequireJsModule('Vendor/Extension/MyJsModule');
            $moduleTemplate->setContent($moduleTemplate->getIconFactory()->getIcon('some-icon', Icon::SIZE_SMALL)->render());
            return $this->htmlResponse($moduleTemplate->renderContent());
        }
    }

This should be migrated to:

.. code-block:: php

    class MyController extends ActionController
    {
        protected ModuleTemplateFactory $moduleTemplateFactory;
        protected IconFactory $iconFactory;
        protected PageRenderer $pageRenderer;

        public function __construct(
            ModuleTemplateFactory $moduleTemplateFactory,
            IconFactory $iconFactory,
            PageRenderer $pageRenderer
        ) {
            $this->moduleTemplateFactory = $moduleTemplateFactory;
            $this->iconFactory = $iconFactory;
            $this->pageRenderer = $pageRenderer;
        }

        public function myAction(): ResponseInterface
        {
            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $this->pageRenderer->loadRequireJsModule('Vendor/Extension/MyJsModule');
            $moduleTemplate->setContent($this->iconFactory->getIcon('some-icon', Icon::SIZE_SMALL)->render());
            return $this->htmlResponse($moduleTemplate->renderContent());
        }
    }

.. index:: Backend, PHP-API, FullyScanned, ext:backend
