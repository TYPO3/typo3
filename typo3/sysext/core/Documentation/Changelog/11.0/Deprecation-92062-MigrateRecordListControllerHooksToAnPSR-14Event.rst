.. include:: /Includes.rst.txt

========================================================================
Deprecation: #92062 - Migrate RecordListController hooks to PSR-14 event
========================================================================

See :issue:`92062`

Description
===========

The following hooks have been marked as deprecated:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']`

Both hooks were used to add content before or after the main content of the list module.

Impact
======

Using the hooks still works as before, but trigger a PHP :php:`E_USER_DEPRECATED` error.
The hooks will be removed and stop working in TYPO3 v12.
Please migrate to the PSR-14 event: :php:`TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent`.


Affected Installations
======================

TYPO3 installations with extensions that hook into the RecordListController.


Migration
=========

The functionality of both hooks has been migrated to the following PSR-14 event:
:php:`TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent`.

The event class contains the following relevant public methods:

* :php:`getRequest`
  Returns the request object from the list module request.
* :php:`addContentAbove`
  Add additional content as string as it is to be shown above the main content.
* :php:`addContentBelow`
  Add additional content as string as it is to be shown below the main content.

The event object is used as parameter for the event listener method (default is :php:`__invoke`).

The listener needs to be registered in the extension: :file:`EXT:myext/Configuration/Services.yaml`.

Example:

.. code-block:: yaml

   My\Extension\Provider\MyAdditionalContentProvider:
      tags:
         - name: event.listener
           identifier: 'my-additional-content'
           event: TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent


Please have a look at :php:`TYPO3\CMS\SysNote\Provider\RecordListProvider` as an example for the
listener implementation.

.. index:: Backend, PHP-API, FullyScanned, ext:recordlist
