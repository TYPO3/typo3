.. include:: /Includes.rst.txt

=================================================
Deprecation: #91814 - AbstractControl::setOnClick
=================================================

See :issue:`91814`

Description
===========

In favor of allowing `Content-Security-Policy` HTTP headers, inline JavaScript
invocation via :php:`\TYPO3\CMS\Backend\Template\Components\AbstractControl::setOnClick`
has been marked as deprecated. Existing instructions can be migrated using existing JavaScript
helpers :js:`GlobalEventHandler` or :js:`ActionDispatcher` and their capabilities to provide
similar functionality using :html:`data-` attributes.

There might be scenarios that require a custom JavaScript module handling
specific use cases that are not covered by mentioned JavaScript helpers.


Impact
======

Using affected PHP methods (see section below) will trigger PHP :php:`E_USER_DEPRECATED` errors.


Affected Installations
======================

All sites using 3rd party extensions that are using following methods directly
or in inherited class implementations:

* :php:`\TYPO3\CMS\Backend\Template\Components\AbstractControl->setOnClick`
* :php:`\TYPO3\CMS\Backend\Template\Components\AbstractControl->getOnClick`


Migration
=========

Mentioned JavaScript helpers cover most common use cases by using :html:`data-`
attributes instead of :html:`onclick` event attributes with corresponding HTML
elements.

*   consider replacing simple :html:`<a ... onclick="window.location.href=[URI]"`
    with plain HTML links like :html:`<a href="[URI]">`
*   replacing :php:`BackendUtility::viewOnClick`,
    :doc:`see documentation & examples <../11.0/Important-91123-AvoidUsingBackendUtilityViewOnClick>`
*   using :html:`data-` attributes for :js:`GlobalEventHandler` and :js:`ActionDispatcher`,
    :doc:`see documentation & examples <../10.4.x/Important-91117-UseGlobalEventHandlerAndActionDispatcherInsteadOfInlineJS>`


Example #1: open a new window/tab
---------------------------------

*   taken from extension `dce`
*   see `corresponding pull-request <https://bitbucket.org/ArminVieweg/dce/pull-requests/97/task-avoid-using-abstractcontrol>`__

.. code-block:: php

    $button->setOnClick(
        'window.open(\'' . $this->getDceEditLink($contentUid) . '\', \'editDcePopup\', ' .
        '\'height=768,width=1024,status=0,menubar=0,scrollbars=1\')'
    );

Code block above being substituted with :js:`ActionDispatcher` capabilities,
using :html:`data-dispatch-action` and :html:`data-dispatch-args` HTML attributes:

.. code-block:: php

    $button->setDataAttributes([
        'dispatch-action' => 'TYPO3.WindowManager.localOpen',
        // JSON encoded representation of JavaScript function arguments
        // (HTML attributes are encoded in \TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton)
        'dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute([
            $this->getDceEditLink($contentUid),
            'editDcePopup',
            'height=768,width=1024,status=0,menubar=0,scrollbars=1',
        ], false)
    ]);


Example #2: preview page in frontend
------------------------------------

*   taken from extension `wizard_crpagetree`
*   see `corresponding pull-request <https://github.com/liayn/t3ext-wizard_crpagetree/pull/8>`__

.. code-block:: php

    $viewButton = $buttonBar->makeLinkButton()
        // @deprecated setOnClick
        ->setOnClick(BackendUtility::viewOnClick($pageUid, '', BackendUtility::BEgetRootLine($pageUid)))
        ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
        ->setIcon($iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
        ->setHref('#');

Code block above being substituted with :php:`\TYPO3\CMS\Backend\Routing\PreviewUriBuilder`
based on :js:`ActionDispatcher` capabilities, using :html:`data-dispatch-action` and
:html:`data-dispatch-args` HTML attributes:

.. code-block:: php

    $previewDataAttributes = PreviewUriBuilder::create($pageUid)
        ->withRootLine(BackendUtility::BEgetRootLine($pageUid))
        ->buildDispatcherDataAttributes();
    $viewButton = $buttonBar->makeLinkButton()
        // substituted with HTML data attributes
        ->setDataAttributes($previewDataAttributes ?? [])
        ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
        ->setIcon($iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
        ->setHref('#');


Example #3: confirmation dialog
-------------------------------

*   taken form extension `news`
*   see `corresponding pull-request <https://github.com/georgringer/news/pull/1585>`__
*   side-note: There was a bug in extension `news`, examples below have been adjusted
    to show how the scenario probably would have been before, using :js:`confirm()`

.. code-block:: php

    $pasteTitle = 'Paste from Clipboard';
    $confirmMessage = GeneralUtility::quoteJSvalue('Shall we paste the record?');
    $viewButton = $buttonBar->makeLinkButton()
        ->setHref($clipBoard->pasteUrl('', $this->pageUid))
        // @deprecated inline JavaScript requesting user confirmation
        ->setOnClick('return confirm(' . $confirmMessage . ')')
        ->setTitle($pasteTitle)
        ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));

Code block above being substituted with capabilities of modal dialog handling
and functionalities of the Bootstrap framework.

.. code-block:: php

    $pasteTitle = 'Paste from Clipboard';
    $confirmMessage = 'Shall we paste the record?';
    $viewButton = $buttonBar->makeLinkButton()
        ->setHref($clipBoard->pasteUrl('', $this->pageUid))
        // using CSS class to trigger confirmation in modal box
        ->setClasses('t3js-modal-trigger')
        ->setDataAttributes([
            'title' => $pasteTitle,
            'bs-content' => $confirmMessage,
        ])
        ->setTitle($pasteTitle)
        ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));


.. index:: Backend, JavaScript, PHP-API, FullyScanned, ext:backend
