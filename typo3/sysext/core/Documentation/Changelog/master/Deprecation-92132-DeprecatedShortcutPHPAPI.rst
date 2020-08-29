.. include:: ../../Includes.txt

=================================================
Deprecation: #92132 - Deprecated shortcut PHP API
=================================================

See :issue:`92132`

Description
===========

Some methods related to :php:`ext:backend` shortcut / bookmark handling have been deprecated:

* :php:`TYPO3\CMS\Backend\Template\ModuleTemplate->makeShortcutIcon()`
* :php:`TYPO3\CMS\Backend\Template\ModuleTemplate->makeShortcutUrl()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getSetVariables()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getGetVariables()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setGetVariables()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setSetVariables()`


Impact
======

Using those methods directly or indirectly will trigger deprecation log warnings.


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
    $shortCutButton = $buttonBar->makeShortcutButton()
        ->setModuleName('web_view')
        ->setArguments([
            'route' => $request->getQueryParams['route'],
            'id' => (int)($request->getQueryParams()['id'] ?? 0),
         ]);
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);

.. index:: Backend, PHP-API, FullyScanned, ext:backend
