.. include:: /Includes.rst.txt

==============================================================
Feature: #87798 - Provide a way to sort form lists in ext:form
==============================================================

See :issue:`87798`

Description
===========

Forms in ext:form were previously not sorted in any manner,
but just outputted in the order they were read from the filesystem's directories.

Forms can now be sorted by multiple keys in either ascending or descending order.
Two new settings were introduced: ``sortByKeys`` and ``sortAscending``.

Here is an example configuration,
that will sort forms by their name first and by their file uid second:

.. code-block:: yaml

   TYPO3:
        CMS:
          Form:
            persistenceManager:
              sortByKeys: ['name', 'fileUid']
              sortAscending: true

Valid keys, by which the forms can be sorted, are:

``name``
   The forms name.

``identifier``
   The filename.

``fileUid``
   The files uid.

``persistenceIdentifier``
   The files location.

   Example: ``1:/form_definitions/contact.form.yaml``

``readOnly``
   Is the form readonly?

``removable``
   Is the form removable?

``location``
   Either `storage` or `extension`

``invalid``
   Does the form have an error?

Impact
======

Forms will now initially be sorted by their name first and their file uid second in an ascending order.
This affects both the form list shown in the form module as well as the ordering of the available select options when creating a new form content element.

To change the sorting, you can override the configuration via YAML as described by the example above.

.. index:: Backend, ext:form
