..  include:: /Includes.rst.txt

..  _important-104027-1750965713:

==============================================================================
Important: #104027 - New ViewHelper argument "module" to define module context
==============================================================================

See :issue:`104027`

Description
===========

Description
===========

A new optional argument :html:`module` has been added to the following ViewHelpers:

- :html:`<be:link.editRecord>`
- :html:`<be:link.newRecord>`
- :html:`<be:uri.editRecord>`
- :html:`<be:uri.newRecord>`

The `module` argument allows integrators to explicitly define the **backend module context**
used when opening the FormEngine to edit or create a record. When set, this module will be
highlighted as active in the backend menu, providing better navigation context.

This is particularly useful in scenarios where the default context cannot be
reliably inferred.

.. note::

    This is only necessary if the ViewHelper cannot determine the
    module context from the request, e.g. when used in an AJAX call.

    If the ViewHelper is used within a backend module, setting the
    `module` argument is usually not required unless a specific
    module context should be enforced deliberately.

Usage Example
=============

.. code-block:: html

   <be:link.editRecord table="tt_content" uid="{record.uid}" module="web_layout">
       Edit this content element
   </be:link.editRecord>

   <be:uri.newRecord table="custom_table" pid="123" module="web_list" />

Impact
======

When used, it ensures a more accurate and predictable backend editing experience
by controlling which module is marked as active when the FormEngine opens.

..  index:: Backend, Fluid, ext:backend
