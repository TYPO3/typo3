.. include:: ../../Includes.txt

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
using a new fluid layout named `Module`.


Impact
======

The standard code within backend module related controllers
looked like this until now:

.. code-block:: php

    $moduleTemplate = $this->moduleTemplateFactory->create($request);
    $view = GeneralUtility::makeInstance(StandaloneView::class);
    $view->setTemplateRootPaths(['EXT:my_extension/Resources/Private/Templates']);
    $view->assign('aVariable', 'aValue');
    $moduleTemplate->setContent($view->render('MyTemplate'));
    return $this->responseFactory->createResponse()
        ->withHeader('Content-Type', 'text/html; charset=utf-8')
        ->withBody($this->streamFactory->createStream($moduleTemplate->renderContent($templateFileName)));

This can be streamline to:

.. code-block:: php

    // @todo: The second argument will fall with one of the next patches, adapt this then.
    $moduleTemplate = $this->moduleTemplateFactory->create($request, $myComposerPackageName);
    $moduleTemplate->assign('aVariable', 'aValue');
    return $moduleTemplate->renderResponse('MyTemplate');

The HTML template should then reference the ModuleTemplate layout:

.. code-block:: html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
    <f:layout name="Module" />
    <f:section name="Content">
        My body content
    </f:section>
    </f:html>

.. index:: Backend, Fluid, PHP-API, ext:backend
