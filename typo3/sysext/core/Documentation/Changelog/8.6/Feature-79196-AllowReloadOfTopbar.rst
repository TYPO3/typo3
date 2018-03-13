.. include:: ../../Includes.txt

========================================
Feature: #79196 - Allow reload of topbar
========================================

See :issue:`79196`

Description
===========

A new JavaScript API to reload the backend's topbar has been introduced to the TYPO3 Core.


Impact
======

The toolbar reloading may be triggered on JavaScript and PHP code-level. To enforce the reloading on PHP side,
call :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal('updateTopbar')`.

Reloading the topbar via JavaScript requires the following code:

.. code-block:: javascript

   // Either: RequireJS style
   define(['TYPO3/CMS/Backend/Viewport'], function(Viewport) {
      Viewport.Topbar.refresh();
   });

   // Or: old-fashioned JavaScript
   if (top && top.TYPO3.Backend && top.TYPO3.Backend.Topbar) {
      top.TYPO3.Backend.Topbar.refresh();
   };


In case a toolbar item registers to the `load` event of the page, the registration must be changed. Reason is that the
event information gets lost, as the whole toolbar is rendered from scratch after a reload.

Example:

.. code-block:: javascript

   define(['jquery', 'TYPO3/CMS/Backend/Viewport'], function($, Viewport) {
      // old registration
      $(MyAwesomeItem.doStuff);

      // new registration
      Viewport.Topbar.Toolbar.registerEvent(MyAwesomeItem.doStuff);
   });

.. index:: Backend, JavaScript, PHP-API
