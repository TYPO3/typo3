
.. include:: ../../Includes.txt

============================================================================
Feature: #66709 - Add TemplateRootPaths support to Fluid/View/StandaloneView
============================================================================

See :issue:`66709`

Description
===========

The StandaloneView is extended with `setTemplateRootPaths($templatePaths)` and `setTemplate($templateName, $throwException = TRUE)`.
Now you can set a template by name.

When `setTemplate($templateName)` is called the `$templateName` is used to find the template in the given
templateRootPaths with the same fallback logic as layoutRootPath and partialRootPath.


Basic example:

.. code-block:: php

	$view = GeneralUtility::makeInstance(StandaloneView::class);
	$view->setLayoutRootPaths($layoutPaths);
	$view->setPartialRootPaths($partialPaths);
	$view->setTemplateRootPaths($templatePaths);

	try {
		$view->setTemplate($templateName);
	} catch (InvalidTemplateResourceException $e) {
		// no template $templateName found in given $templatePaths
		exit($e->getMessage());
	}

	$content = $view->render();



Example of rendering a email template:

.. code-block:: php

	$view = GeneralUtility::makeInstance(StandaloneView::class);
	$view->setLayoutRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:my_extension/Resources/Private/Layouts')));
	$view->setPartialRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:my_extension/Resources/Private/Partials')));
	$view->setTemplateRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:my_extension/Resources/Private/Templates')));
	$view->setTemplate('Email/Notification');

	$emailBody = $view->render();


Impact
======

The public API of `TYPO3\CMS\Fluid\View\StandaloneView` is enhanced with the methods
 `setTemplateRootPaths($templatePaths)` and `setTemplate($templateName, $throwException = TRUE)`
