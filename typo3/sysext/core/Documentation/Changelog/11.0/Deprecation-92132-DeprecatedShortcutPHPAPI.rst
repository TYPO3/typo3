.. include:: /Includes.rst.txt

======================================
Deprecation: #92132 - Shortcut PHP API
======================================

See :issue:`92132`

Description
===========

Some methods related to shortcut / bookmark handling in TYPO3 Backend have been marked as deprecated:

* :php:`TYPO3\CMS\Backend\Template\ModuleTemplate->makeShortcutIcon()`
* :php:`TYPO3\CMS\Backend\Template\ModuleTemplate->makeShortcutUrl()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getSetVariables()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getGetVariables()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setGetVariables()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setSetVariables()`

See also:

- :ref:`changelog-Deprecation-93060-ShortcutTitleMustBeSetByControllers`
- :ref:`changelog-Deprecation-93093-DeprecateMethodNameInShortcutPHPAPI`


Impact
======

Using those methods directly or indirectly will trigger PHP :php:`E_USER_DEPRECATED` errors.


Affected Installations
======================

Extensions with backend modules that show the shortcut button in the doc header may
be affected. The extension scanner will find all PHP usages as weak match.


Migration
=========

The new method :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setArguments()` has been
introduced. This method expects the full set of arguments and values to create a shortcut to a specific view, example:

.. code-block:: php

   $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
   $pageId = (int)($request->getQueryParams()['id'] ?? 0);
   $shortCutButton = $buttonBar->makeShortcutButton()
       ->setRouteIdentifier('web_ViewpageView')
       ->setDisplayName('View page ' . $pageId)
       ->setArguments([
          'id' => $pageId,
       ]);
   $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);

.. index:: Backend, PHP-API, FullyScanned, ext:backend
