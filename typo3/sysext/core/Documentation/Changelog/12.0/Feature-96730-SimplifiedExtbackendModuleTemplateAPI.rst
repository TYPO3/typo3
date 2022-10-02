.. include:: /Includes.rst.txt

.. _feature-96730:

===========================================================
Feature: #96730 - Simplified ext:backend ModuleTemplate API
===========================================================

See :issue:`96730`

Description
===========

Extensions that deliver own backend modules can now use the
:php:`TYPO3\CMS\Backend\Template\ModuleTemplate` class as
view for the 'body' part of the view and do not need to
instantiate an own view anymore.

The default document header of :php:`ModuleTemplate` can be rendered
using a new Fluid layout named `Module`.

Impact
======

The standard code within backend module related controllers
looked like this until now:

..  code-block:: php

    $moduleTemplate = $this->moduleTemplateFactory->create($request);
    $view = GeneralUtility::makeInstance(StandaloneView::class);
    $view->setTemplateRootPaths(['EXT:my_extension/Resources/Private/Templates']);
    $view->assign('aVariable', 'aValue');
    $moduleTemplate->setContent($view->render('MyTemplate'));
    return $this->responseFactory->createResponse()
        ->withHeader('Content-Type', 'text/html; charset=utf-8')
        ->withBody($this->streamFactory->createStream($moduleTemplate->renderContent($templateFileName)));

This can be streamlined as shown below. Template paths (Templates, Layouts, Partials)
are configured automatically, calling :php:`renderResponse('SomeController/SomeAction')` will look for file
:file:`Resources/Private/Templates/SomeController/SomeAction.html`. Templates can be
overridden by other extensions using page TSconfig, see :doc:`this changelog entry <Feature-96812-OverrideBackendTemplatesWithTSconfig>`
for details on this.

..  code-block:: php

    $moduleTemplate = $this->moduleTemplateFactory->create($request);
    $moduleTemplate->assign('aVariable', 'aValue');
    return $moduleTemplate->renderResponse('MyTemplate');

The HTML template should then reference the ModuleTemplate layout:

..  code-block:: html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
    <f:layout name="Module" />
    <f:section name="Content">
        My body content
    </f:section>
    </html>

.. index:: Backend, Fluid, PHP-API, ext:backend
