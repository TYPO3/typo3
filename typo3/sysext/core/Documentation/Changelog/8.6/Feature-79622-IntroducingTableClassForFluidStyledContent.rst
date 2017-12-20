.. include:: ../../Includes.txt

==================================================================
Feature: #79622 - Introducing Table Class for Fluid Styled Content
==================================================================

See :issue:`79622`

Description
===========

In CSS Styled Content it is possible to provide additional CSS classes
for the rendering of the table content element. This feature is now
available for Fluid Styled Content, too.

The default layout of the Fluid Styled Content element table is now passing
the value of `Table Class` directly to the template and prefixes the value
by default with `ce-table-<key>`.


Implementation in Fluid Styled Content
--------------------------------------

.. code-block:: html

   <table class="ce-table{f:if(condition: data.table_class, then: ' ce-table-{data.table_class}')}">
      ...
   </table>


Explanation of Keys and Effects of Table Classes
-------------------------------------------------

===============   ===============   =====================   ==============================================
Name              Key               CSS Class               Additional Effects
===============   ===============   =====================   ==============================================
Default           (empty)           (none)                  (none)
Stiped            striped           ce-table-striped        Odd rows will be highlighted
Bordered          bordered          ce-table-bordered       A border will be displayed around the table
===============   ===============   =====================   ==============================================

Please note that you need to include the optional static template "Fluid Styled
Content Styling" to have a visual effect on the new added CSS classes.


Default styling for optional "Fluid Styled Content Styling"
-----------------------------------------------------------

.. code-block:: css

   /* Table */
   .ce-table {
      width: 100%;
      max-width: 100%;
   }
   .ce-table th,
   .ce-table td {
      padding: 0.5em 0.75em;
      vertical-align: top;
   }
   .ce-table thead th {
      border-bottom: 2px solid #dadada;
   }
   .ce-table th,
   .ce-table td {
      border-top: 1px solid #dadada;
   }
   .ce-table-striped tbody tr:nth-of-type(odd) {
      background-color: rgba(0,0,0,.05);
   }
   .ce-table-bordered th,
   .ce-table-bordered td {
      border: 1px solid #dadada;
   }


Edit predefined options
-----------------------

.. code-block:: typoscript

   TCEFORM.tt_content.table_class {
      removeItems = striped,bordered
      addItems {
         supertable = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:supertable
      }
   }

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['table_class']['config']['items'][] = [
      0 = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:supertable
      1 = supertable
   ];


Impact
======

`Table Class` is now available for the Fluid Styled Content table content element.


.. index:: Fluid, Frontend, ext:fluid_styled_content, TypoScript
