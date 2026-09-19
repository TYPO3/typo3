..  include:: /Includes.rst.txt

..  _important-76274-1789813130:

================================================================
Important: #76274 - Language field is no longer an exclude field
================================================================

See :issue:`76274`

Description
===========

The TCA column referenced by :php:`$GLOBALS['TCA'][$table]['ctrl']['languageField']`
is no longer marked as :php:`'exclude' => true`.

Access to this field is governed by the backend user group permission
:php:`allowed_languages`, which is evaluated in both the form and in
:php:`DataHandler`. Marking the column as an "exclude field" added a second,
unrelated permission on top of it: :php:`non_exclude_fields`.

That second permission caused actual damage. Whenever a backend user without the
exclude field right for the language field triggered a write, :php:`DataHandler`
silently dropped the language value. Translating a record therefore created a
record in the *default* language that still pointed to its source record, and
copying a translated record moved the copy into the default language. No error
was raised, and editors restricted to a non-default language could not see the
resulting record at all.

Three changes were made:

*   The automatically created :php:`columns` definition for the language field no
    longer contains :php:`exclude`.
*   A TCA migration removes :php:`exclude` from the language field if a table
    defines the column itself, or re-adds the flag in a
    :file:`Configuration/TCA/Overrides` file.
*   The TCA schema field type for :php:`'type' => 'language'` never reports
    access control, so an :php:`exclude` flag set elsewhere, for example in
    :php:`columnsOverrides`, is ignored as well.

Impact
======

Entries of the form :php:`<table>:<languageField>` in the :php:`non_exclude_fields`
permission of a backend user group no longer have any effect and can be removed.

The visibility of the language field now follows the language permissions alone:
:php:`TcaColumnsRemoveEmptyRelations` removes the field from the form when fewer
than two languages are available to the current backend user, so editors
restricted to a single language do not see it. Editors with access to two or more
languages now see the language selector on tables that render it in a palette,
and can pick any language covered by their :php:`allowed_languages`.

Extensions that define the language column in their own
:file:`Configuration/TCA/<table>.php` should remove the :php:`exclude` key from
that definition. The migration logs a deprecation message naming the affected
table until this is done.

..  index:: Backend, TCA, ext:core
