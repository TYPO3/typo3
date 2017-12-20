.. include:: ../../Includes.txt

=======================================================================
Breaking: #79622 - CSS Styled Content table content element adjustments
=======================================================================

See :issue:`79622`

Description
===========

In order to streamline the options and enhance compatibility across CSS Styled
Content and Fluid Styled Content the table content element has been partly
refactored. All previous flexform configuration has been migrated to database
fields, shared across both content rendering definitions.

Element options removed:
- Table Summary
- No CSS styles for this table

Element options changed:
- Additional CSS Class

Rendering changes:
- Additional CSS classes for tr, th, td have been dropped

TypoScript options removed:
- color
- tableParams_0
- tableParams_1
- tableParams_2
- tableParams_3
- border
- cellpadding
- cellspacing


Table Summary
-------------
The <table> summary attribute is not supported in HTML5 and has been dropped.
No migration path available.


No CSS styles for table
-----------------------
The default CSS styling for CSS Styled Content is now optional. If no styling
is required, simply do not include the optional static template or override
the styling with CSS.


Additional CSS Class
--------------------
The process of adding additional CSS classes for tables has been changed.
To ease the work of the editor the CSS class field is no longer a simple input
field. Adding CSS classes are now handled by predefined CSS classes that can
be adjusted by the integrator. Classes will be prefixed with "contenttable-".

.. code-block:: typoscript

   TCEFORM.tt_content.table_class {
      removeItems = striped,bordered
      addItems {
         hover = LLL:my_extension/Resources/Private/language.xlf:hover
      }
   }

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['table_class']['config']['items'][] = [
      0 = LLL:my_extension/Resources/Private/language.xlf:hover
      1 = hover
   ];


Rendering changes and removed TypoScript options
------------------------------------------------
Style specific options have been removed and are no longer available. This
includes the following options for table rendering: `color`, `tableParams_0`,
`tableParams_1`, `tableParams_2`, `tableParams_3`, `border`, `cellpadding` and
`cellspacing`. Also additional CSS classes for `tr`, `th` and `td` are no
longer available.


Affected Installations
======================

Installations that use the CSS Styled Content element table.


Migration
=========

Run the upgrade wizard in the install tool to migrate all fields previously
stored in flexforms to dedicated fields in the database.


Table summary
-------------
The <table> summary attribute is not supported in HTML5 and has been dropped.
No migration path available.


No CSS styles for this table
----------------------------
Remove the optional "CSS Styled Content Styling" static template.


Additional CSS classes
----------------------
Additional CSS Classes must be registered as items for the field `table_class`.


Rendering changes and removed TypoScript Options
------------------------------------------------
Use CSS styling to restore the look of your tables.


.. index:: FlexForm, Frontend, TCA, TypoScript, ext:css_styled_content
