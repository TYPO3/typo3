.. include:: /Includes.rst.txt

================================================================================
Feature: #92462 - Add optional "defaultValues" argument to newRecord ViewHelpers
================================================================================

See :issue:`92462`

Description
===========

A new optional argument :html:`defaultValues` is added to the :html:`be:uri.newRecord` and
:html:`be:link.newRecord` ViewHelpers. The new argument can contain default values for
fields of the new record. FormEngine automatically fills the given default values
into the corresponding fields.

The syntax is: :html:`{tableName: {fieldName: 'value'}}`.

Please note that the given default values are added to the url as :html:`GET` parameters
and therefore override default values defined in FormDataProviders or TSconfig.


Impact
======

It is now possible to assign default values to fields of new records using the
`defaultValues` argument in the `be:uri.newRecord` and `be:link.newRecord` ViewHelpers.


Example
=======

Link to create a new `tt_content` record on page 17 with a default value for field `header`:

.. code-block:: xml

   <be:link.newRecord table="tt_content" pid="17" defaultValues="{tt_content: {header: 'value'}}" returnUrl="foo/bar">
        New record
   </be:link.newRecord>

Output:

.. code-block:: html

   <a href="/typo3/index.php?route=/record/edit&edit[tt_content][17]=new&returnUrl=foo/bar&defVals[tt_content][header]=value">
       New record
   </a>

.. index:: Backend, Fluid, ext:backend
