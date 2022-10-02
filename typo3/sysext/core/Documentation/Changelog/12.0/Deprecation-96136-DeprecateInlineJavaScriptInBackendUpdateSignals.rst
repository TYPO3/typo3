.. include:: /Includes.rst.txt

.. _deprecation-96136:

===========================================================================
Deprecation: #96136 - Deprecate inline JavaScript in backend update signals
===========================================================================

See :issue:`96136`

Description
===========

When changing data via the backend user interface a so called *update signal*
is triggered to update other components like page tree or toolbar items in the
document header bar.

Using inline JavaScript for handing custom signals is deprecated and will be
ignored in TYPO3 v13.0.

Impact
======

Retrieving signals via :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode`
or having custom signal callbacks defined in :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']`
which provide `JScode` are deprecated and will trigger a corresponding PHP
error message.

Affected Installations
======================

see impact

Migration
=========

`BackendUtility::getUpdateSignalCode`
-------------------------------------

In case :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode`
is called directly, new :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalDetails`
shall be used, which is supposed to return safe HTML markup instead of inline
JavaScript code.

Custom signal callbacks
-----------------------

Usually those custom signal hooks are declared like this:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']
        ['updateSignalHook']['OpendocsController::updateNumber'] =
        OpendocsToolbarItem::class . '->updateNumberOfOpenDocsHook';

Existing implementation using `JScode`
......................................

..  code-block:: php

    class OpendocsToolbarItem
    {
        public function updateNumberOfOpenDocsHook(&$params)
        {
            $params['JScode'] = '
                if (top && top.TYPO3.OpendocsMenu) {
                    top.TYPO3.OpendocsMenu.updateMenu();
                }
            ';
        }
    }

Using :php:`JScode` (containing inline JavaScript) is deprecated and
is subject to be migrated.

Potential migration using HTML markup
.....................................

TYPO3 v11 introduced some special markup helpers and components that
allow to dispatch actions, without actually using inline JavaScript.

* :php:`\TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement`,
  which creates a HTML web-component containing the actual relevant payload like
  :html:`<typo3-immediate-action action="..." args="..."></typo3-immediate-action>`
* :php:`\TYPO3\CMS\Core\Page\JavaScriptModuleInstruction` rendered using
  :php:`\TYPO3\CMS\Core\Page\JavaScriptRenderer::render`, which uses a script helper
  that loads JavaScript modules and invokes a method or assigns variables globally, e.g.
  :html:`<script src="/typo3/sysext/core/Resources/Public/JavaScript/JavaScriptItemHandler.js" ...>`

**Side-note**: Just using markup like :html:`<script>alert(1)</script>`
is **not** considered a good solution as it still contains inline JavaScript.

..  code-block:: php

    class OpendocsToolbarItem
    {
        public function updateNumberOfOpenDocsHook(&$params)
        {
            $params['html'] = ImmediateActionElement::dispatchCustomEvent(
                'typo3:opendocs:updateRequested',
                null,
                true
            );
        }
    }

:php:`ImmediateActionElement` is utilized to trigger a custom event in JavaScript,
which is handled by a custom JavaScript module in that particular case.

..  code-block:: typescript

    class OpendocsMenu {
      constructor() {
        document.addEventListener(
          'typo3:opendocs:updateRequested',
          (evt: CustomEvent) => this.updateMenu(),
        );
      }

.. index:: Backend, JavaScript, FullyScanned, ext:backend
