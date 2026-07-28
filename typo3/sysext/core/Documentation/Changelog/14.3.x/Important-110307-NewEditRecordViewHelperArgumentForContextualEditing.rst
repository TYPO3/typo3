..  include:: /Includes.rst.txt

..  _important-110307-1753690555:

==============================================================================
Important: #110307 - New editRecord ViewHelper argument for contextual editing
==============================================================================

See :issue:`110307`

Description
===========

A new optional boolean argument :html:`contextual` has been added to the
Fluid ViewHelper :html:`<be:link.editRecord>`.

When enabled, the ViewHelper renders a
:html:`<typo3-backend-contextual-record-edit-trigger>` element instead of a
classic edit link. It builds a URL for the :php:`record_edit_contextual`
backend route and a fallback URL for the classic :php:`record_edit` route.
It also loads the required JavaScript module
:js:`@typo3/backend/element/contextual-record-edit-trigger.js`, so custom
backend modules do not need to register that asset manually.

This makes the contextual editing behavior already used in the layout module
available to backend Fluid templates without custom PHP markup.

Existing usages of :html:`<be:link.editRecord>` remain unchanged.


Usage Example
=============

..  code-block:: html
    :caption: Example usage of contextual argument

    <be:link.editRecord
        uid="{record.uid}"
        table="pages"
        fields="title,subtitle"
        contextual="true"
        class="btn btn-default"
    >
        Edit page properties
    </be:link.editRecord>


Impact
======

Extension authors can use contextual record editing in backend Fluid
templates, for example in custom backend previews, while keeping the shared
URL-building logic of :html:`<be:link.editRecord>`.

..  index:: Backend, Fluid, ext:backend
