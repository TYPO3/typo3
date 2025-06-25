..  include:: /Includes.rst.txt

..  _feature-106972-1750856721:

==============================================
Feature: #106972 - Configure searchable fields
==============================================

See :issue:`106972`

Description
===========

TYPO3 now automatically includes all fields of suitable types in
backend search operations, e.g., in the List module.

This eliminates the need for the previously used TCA `ctrl` option
:php:`searchFields`, which has been :ref:`removed <breaking-106972-1750856858>`.

Instead, a new per-field configuration option :php:`searchable`
has been introduced. It allows integrators to fine-tune whether
a specific field should be included in backend search queries.

By default, all fields of supported types are considered searchable.
To exclude a field from being searchable, set the following in the
field’s TCA configuration:

.. code-block:: php

   'my_field' => [
       'config' => [
           'type' => 'input',
           'searchable' => false,
       ],
   ],

Note that until :php:`searchFields` is manually removed from your TCA, the
automatic TCA migration sets all suitable fields, which are not included
in the :php:`searchFields` configuration, to :php:`searchable => false` to
keep current behavior.

Supported Field Types
----------------------

The following TCA field types support the :php:`searchable` option and are
automatically considered in searches unless explicitly excluded:

* :php:`color`
* :php:`datetime` (when not using a custom :php:`dbType`)
* :php:`email`
* :php:`flex`
* :php:`input`
* :php:`json`
* :php:`link`
* :php:`slug`
* :php:`text`
* :php:`uuid`

Unsupported field types such as :php:`file`, :php:`inline`, :php:`password` or
:php:`group` are excluded from search and do not support the
:php:`searchable` option.

Impact
======

- Backend search becomes more consistent and automatic.
- No need to manually maintain a :php:`searchFields` list in TCA.
- Integrators have more granular control over search behavior on a field level.
- Custom fields can easily be excluded from search using the :php:`searchable` option.

Migration
=========

If your extension previously relied on the :php:`searchFields` TCA option,
remove it from the :php:`ctrl` section and instead define :php:`'searchable' => false`
on fields that should be excluded from search results.

No action is needed if the default behavior (search all suitable fields)
is acceptable.

Example
=======

.. code-block:: php

   return [
       'columns' => [
           'title' => [
               'config' => [
                   'type' => 'input',
                   'searchable' => true, // optional, true by default
               ],
           ],
           'notes' => [
               'config' => [
                   'type' => 'text',
                   'searchable' => false, // explicitly excluded
               ],
           ],
       ],
   ];

..  index:: TCA, ext:core
