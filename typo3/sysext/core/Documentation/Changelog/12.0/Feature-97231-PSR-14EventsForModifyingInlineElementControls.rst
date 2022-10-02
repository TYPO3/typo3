.. include:: /Includes.rst.txt

.. _feature-97231:

=====================================================================
Feature: #97231 - PSR-14 events for modifying inline element controls
=====================================================================

See :issue:`97231`

Description
===========

The new PSR-14 events :php:`\TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent`
and :php:`\TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent`
have been introduced, which serve as a more powerful and flexible replacement
for the now removed hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook']`.

The :php:`\TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent`
is called before any control markup is generated. It can be used to
enable or disable each control. With this event it's therefore possible
to e.g. enable a control, which is disabled in TCA, only for some use case.

The :php:`\TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent`
is called after the markup for all enabled controls has been generated. It
can be used to either change the markup of a control, to add a new control
or to completely remove a control.

.. note::

    Previously the deprecated hook interface :php:`InlineElementHookInterface`
    required hook implementations to implement both methods
    :php:`renderForeignRecordHeaderControl_preProcess()` and
    :php:`renderForeignRecordHeaderControl_postProcess()`, even if only
    one was used. This is now resolved since listeners can be registered
    only for the needed PSR-14 event.

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Frontend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/backend/modify-enabled-controls'
          method: 'modifyEnabledControls'
        - name: event.listener
          identifier: 'my-package/backend/modify-controls'
          method: 'modifyControls'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent;
    use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent;
    use TYPO3\CMS\Core\Imaging\Icon;
    use TYPO3\CMS\Core\Imaging\IconFactory;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    class MyEventListener
    {

        public function modifyEnabledControls(
            ModifyInlineElementEnabledControlsEvent $event
        ): void {
            // Enable a control depending on the foreign table
            if ($event->getForeignTable() === 'sys_file_reference'
                && $event->isControlEnabled('sort')) {
                $event->enableControl('sort');
            }
        }

        public function modifyControls(
            ModifyInlineElementControlsEvent $event
        ): void {
            // Add a custom control depending on the parent table
            if ($event->getElementData()['inlineParentTableName'] === 'tt_content') {
                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $iconCode = $iconFactory->getIcon(
                    'my-icon-identifier',
                    Icon::SIZE_SMALL
                )->render();
                $event->setControl(
                    'tx_my_control',
                    '<a href="/some/url" class="btn btn-default t3js-modal-trigger">'
                    . $iconCode . '</a>'
                );
            }
        }
    }

Available Methods
=================

The list below describes all specific methods for the :php:`ModifyInlineElementEnabledControlsEvent`:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| enableControl()         | :php:`$identifier`    | Enable a control, if it exists. Returns whether    |
|                         |                       | the control could be enabled.                      |
+-------------------------+-----------------------+----------------------------------------------------+
| disableControl()        | :php:`$identifier`    | Disable a control, if it exists. Returns whether   |
|                         |                       | the control could be disabled.                     |
+-------------------------+-----------------------+----------------------------------------------------+
| hasControl              | :php:`$identifier`    | Whether a control exists for the given identifier. |
+-------------------------+-----------------------+----------------------------------------------------+
| isControlEnabled()      | :php:`$identifier`    | Returns whether the control is enabled. Will also  |
|                         |                       | return :php:`false` in case no control exists for  |
|                         |                       | the requested identifier.                          |
+-------------------------+-----------------------+----------------------------------------------------+
| getControlsState()      | :php:`$identifier`    | Returns all controls with their state (enabled     |
|                         |                       | or disabled).                                      |
+-------------------------+-----------------------+----------------------------------------------------+
| getEnabledControls()    |                       | Returns only the enabled controls.                 |
+-------------------------+-----------------------+----------------------------------------------------+

The list below describes all specific methods for the :php:`ModifyInlineElementControlsEvent`:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| getControls()           |                       | Returns all controls with their markup.            |
+-------------------------+-----------------------+----------------------------------------------------+
| setControls()           | :php:`$controls`      | Overwrite the controls.                            |
+-------------------------+-----------------------+----------------------------------------------------+
| getControl()            | :php:`$identifier`    | Returns the markup for the requested control.      |
+-------------------------+-----------------------+----------------------------------------------------+
| setControl()            | :php:`$identifier`    | Set a control with the given identifier and        |
|                         | :php:`$markup`        | markup. Overwrites an existing control with the    |
|                         |                       | same identifier.                                   |
+-------------------------+-----------------------+----------------------------------------------------+
| hasControl()            | :php:`$identifier`    | Returns whether a control exists for the given     |
|                         |                       | identifier.                                        |
+-------------------------+-----------------------+----------------------------------------------------+
| removeControl()         | :php:`$identifier`    | Removes a control from the inline element. Returns |
|                         |                       | whether the control could be disabled.             |
+-------------------------+-----------------------+----------------------------------------------------+

The list below describes all common methods of both events:

+-------------------------+----------------------------------------------------------------------------+
| Method                  | Description                                                                |
+=========================+============================================================================+
| getElementData()        | Returns the whole element data.                                            |
+-------------------------+----------------------------------------------------------------------------+
| getRecord()             | Returns the current record of the controls are created for.                |
+-------------------------+----------------------------------------------------------------------------+
| getParentUid()          | Returns the uid of the parent (embedding) record (uid or NEW...).          |
+-------------------------+----------------------------------------------------------------------------+
| getForeignTable()       | Returns the table (foreign_table) the controls are created for.            |
+-------------------------+----------------------------------------------------------------------------+
| getFieldConfiguration() | Returns the TCA configuration of the inline record field.                  |
+-------------------------+----------------------------------------------------------------------------+
| isVirtual()             | Returns whether the current records is only virtually shown and not        |
|                         | physically part of the parent record.                                      |
+-------------------------+----------------------------------------------------------------------------+

Impact
======

The main advantages of the new PSR-14 events are an increased amount of
available information, the object-oriented approach as well as the new
built-in convenience features.

Additionally, it's no longer necessary to implement empty methods, required
by the interface.

.. index:: Backend, PHP-API, ext:backend
