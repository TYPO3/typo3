.. include:: /Includes.rst.txt

===========================================================
Important: #91123 - Avoid using BackendUtility::viewOnClick
===========================================================

See :issue:`91123`

Description
===========

:php:`BackendUtility::viewOnClick()` is not used anymore in TYPO3 core to
reduce the amount of inline JavaScript being generated in the backend user
interface. :php:`\TYPO3\CMS\Backend\Routing\PreviewUriBuilder` should be
used instead.

Probably :php:`BackendUtility::viewOnClick()` will be deprecated in TYPO3 v11
and finally removed in TYPO3 v12.0 - for TYPO3 v10 LTS it's still available.

The following example demonstrates how implementations can be adjusted to
make use of the new functionality without using inline JavaScript.

.. code-block:: php

   $onclick = \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick(
       $pageId, $backPath, $rootLine, $section,
       $viewUri, $getVars, $switchFocus
   );
   $serializedAttributes = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes([
       'href' => '#',
       'onclick' => $onclick,
   ], true);
   $html = '<a ' . $serializedAttributes . '>...</a>';

Above code snipped can be migrated to the following, given that RequireJS module
:js:`TYPO3/CMS/Backend/ActionDispatcher` is loaded (which basically is the case
in most backend modules).

.. code-block:: php

    $attributes = \TYPO3\CMS\Backend\Routing\PreviewUriBuilder::create($pageId, $viewUri)
        ->withRootLine($rootLine)
        ->withSection($section)
        ->withAdditionalQueryParameters($getVars)
        ->buildDispatcherDataAttributes([
            \TYPO3\CMS\Backend\Routing\PreviewUriBuilder::OPTION_SWITCH_FOCUS => $switchFocus,
        ]);
   $serializedAttributes = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes([
       'href' => '#',
       'data-dispatch-action' => $attributes['data-dispatch-action'],
       'data-dispatch-args' => $attributes['data-dispatch-args'],
   ], true);
   $html = '<a ' . $serializedAttributes . '>...</a>';

Generated :php:`$attributes` can be used directly of course, the example above
was used to actually show the result and existence of those new data-attributes.


.. index:: Backend, PHP-API, FullyScanned, ext:backend
