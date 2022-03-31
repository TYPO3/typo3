.. include:: /Includes.rst.txt

======================================================================
Feature: #91859 - Allow SelectCheckBox groups to be initially expanded
======================================================================

See :issue:`91859`

Description
===========

A new TCA setting `expandAll` has been added to FormEngine type `select` with
renderType `selectCheckBox`. It allows to define the initial display behavior
for grouped checkboxes.

By adding the new setting :php:`'expandAll' => true`, all select groups are
initially expanded.

Please note, that the new setting is placed in :php:`['config']['appearance']`
and is not a top level configuration key. Therefore the full path is:
:php:`$GLOBALS['TCA'][$table]['columns'][$field]['config']['appearance']['expandAll']`


Example
=======

.. code-block:: php

   'select_checkbox' => [
       'label' => 'select_checkbox - expandAll',
       'config' => [
           'type' => 'select',
           'renderType' => 'selectCheckBox',
           'appearance' => [
               'expandAll' => true
           ],
           'items' => [
               ['group 1', '--div--'],
               ['check 1', 1],
               ['check 2', 2],
               ['check 3', 3],
               ['group 2', '--div--'],
               ['check 4', 4],
               ['check 5', 5]
           ]
       ]
   ]


Impact
======

It's now possible to initially expand all checkbox groups. Integrators can
therefore provide their editors with all the choices at once, without
having to open each select group individually. The possibility to close each
group remains unchanged for the editor.

.. index:: TCA
