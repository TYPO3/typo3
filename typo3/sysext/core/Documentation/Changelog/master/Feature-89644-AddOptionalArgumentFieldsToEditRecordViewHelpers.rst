.. include:: ../../Includes.txt

==========================================================================
Feature: #89644 - Add optional argument "fields" to editRecord ViewHelpers
==========================================================================

See :issue:`89644`

Description
===========

An optional argument "fields" is added to the uri.editRecord and link.editRecord ViewHelper.
This can contain the names of one or more database fields (comma separated).

If the argument "fields" is set, FormEngine creates a form to edit only these fields.


Impact
======

This ViewHelper passes the value given in the `fields` argument to the backend route
'/record/edit` as `columnsOnly` argument.

The functionality for `columnsOnly` already existed for the backend route
`/record/edit` before this patch.

Example
=======

Create a link to edit the `tt_content.bodytext` field of record with uid 42:

.. code-block:: xml

   <be:link.editRecord uid="42" table="tt_content" fields="bodytext" returnUrl="foo/bar">
        Edit record
   </be:link.editRecord>

Output:

.. code-block:: html


   <a href="/typo3/index.php?route=/record/edit&edit[tt_content][42]=edit&returnUrl=foo/bar&columnsOnly=bodytext">
       Edit record
   </a>



.. index:: Fluid, ext:backend
