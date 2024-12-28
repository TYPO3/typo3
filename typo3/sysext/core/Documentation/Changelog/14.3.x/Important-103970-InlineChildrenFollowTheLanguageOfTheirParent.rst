..  include:: /Includes.rst.txt

..  _important-103970-1792102654:

========================================================================
Important: #103970 - Inline children follow the language of their parent
========================================================================

See :issue:`103970`

Description
===========

Records in inline relations of type :php:`'inline'` and type :php:`'file'`
belong to exactly one parent record. DataHandler now ensures that these
children always have the same language as their parent when records are
persisted.

Before, children could end up with a different language than their parent:

*   New children created together with their parent kept the language the
    backend form sent for them, which was the language of the parent when the
    form was opened, not the one selected before saving.

*   New children added to an existing record in a non-default language got
    the default language when no language was submitted for them.

*   Existing children kept their language when the language of their parent
    was changed, for example from "All languages" to the default language.
    Localizing such a parent duplicated the children, or failed with
    "Child record was not processed" when
    :php:`'allowLanguageSynchronization'` is enabled for the inline field.

Language of inline children on persistence
------------------------------------------

Once all relations of a DataHandler run are resolved, the language of the
children is aligned for each parent record that

*   has been created,
*   had its language changed, or
*   received new inline children.

All children of such a parent are aligned, including existing children when
only a new child is added. The language stored for the parent record is used,
not the one submitted for it. Nested inline children follow their aligned
parent, so a whole tree of inline records ends up in the same language,
regardless of the order of the records in the data map.

The children are aligned after the records have been written. Hooks of
:php:`processDatamap_afterDatabaseOperations` therefore still receive the
language submitted for a child. The aligned language is available in
:php:`processDatamap_afterAllOperations` and in the database.

A child that is aligned to the default language or to "All languages" is no
translation anymore. Its translation parent (:php:`transOrigPointerField`)
and translation source (:php:`translationSource`) are reset as well. The
translation pointers of the parent record itself are not changed.

This applies to inline relations using :php:`'foreign_field'` as well as to
inline relations stored as comma-separated list in the parent field, and to
all fields of type :php:`'file'`.

Permissions and workspaces
--------------------------

The language of an inline child is not edited on its own, it follows from its
parent. It is therefore written like the pointer to the parent and the sorting
DataHandler already writes to inline children: without checking the access to
the child table, its page or its language field. An editor who is allowed to
change the language of a parent record does not need access to the language
field of the child table, which is excluded by default.

In a workspace, only workspace versions of the children are written. A
workspace version is created if a child does not have one yet. Live records
are never changed from a workspace, and children of tables that are not
workspace aware are left untouched.

Recommendation for TCA of inline child tables
---------------------------------------------

Since DataHandler sets the language of inline children from their parent,
the language field (:php:`languageField`) and the translation parent
(:php:`transOrigPointerField`) of a table that is only used as inline child
do not need to be part of its :php:`'showitem'` configuration anymore, neither
as visible fields nor in a hidden palette.

Rendering an editable language field for inline children is discouraged: an
editor could change the language of a single child, which is not aligned back
to the language of its parent.

Keep the fields in :php:`'showitem'` for tables that are also edited on their
own, outside of an inline relation, and for children of inline relations
stored in an MM table.

What is not covered
-------------------

*   Inline relations stored in an MM table (:php:`'MM'`) are not aligned.
    Their children are not owned by a single parent: the same child can be
    related to several parents with different languages, and with
    :php:`'MM_opposite_field'` both records are parent and child of each
    other.

*   A child that is listed in the comma-separated inline field of several
    parents with different languages gets the language of the parent that is
    processed last.

*   Changing the language of an inline child directly, for example in a
    backend form where the language field of the child is editable, is not
    aligned back to the language of its parent.

*   Records that already have a different language than their parent are not
    changed automatically. They are aligned the next time the language of
    their parent is changed or new children are added to it.

*   Changing the language of a record does not reset its own translation
    pointers. A translated record that is copied to another page and switched
    to the default language still points to its original translation parent.

..  index:: Backend, Database, TCA, ext:core
