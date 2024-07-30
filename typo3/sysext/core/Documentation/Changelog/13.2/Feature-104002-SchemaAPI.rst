.. include:: /Includes.rst.txt

.. _feature-104002-1718273913:

=============================
Feature: #104002 - Schema API
=============================

See :issue:`104002`

Description
===========

A new Schema API is introduced to access information about all TCA structures
in a unified way.

The main goal of this architecture is to reduce direct access to
:php:`$GLOBALS['TCA']` after the Bootstrap process is completed.

The Schema API implements the following design goals:

1. An object-oriented approach to access common TCA information such as if a
database table is localizable or workspace-aware, if it has a "deleted" field
("soft-delete"), or other common functionality such as "enableFields" / "enablecolumns",
which can be accessed via "Capabilities" within a Schema.

2. A unified way to access information which "types" a TCA table has available,
such as "tt_content", where the "CType" field is the divisor for types, thus,
allowing a Schema to have sub-schemata for a TCA Table.

The API in turn then handles which fields are available for a specific "CType".
An example is "tt_content" with type "textpic": The sub-schema "tt_content.textpic"
only contains the fields that are registered of that "CType", such as "bodytext",
which then knows it is a Rich Text Field (the default column does not have this information),
or "image" (a file relation field), but the sub-schema does not contain fields
that are irrelevant for this type, such as "assets" (also a file relation field).

3. An abstracted way to available TCA field types such as "input" or "select",
which also takes information into account, if a select field is a selection of a
static list (such as "pages.layout") or if it contains a relation to another
schema or field (based on "foreign_table"). Previously, this was evaluated in
many places in TYPO3 Core, and can now be reduced subsequently.

Thus, Schema API can now be utilized to determine the :php:`RelationshipType`
of a relational field type in a unified way without having to deal with deeply
nested arrays.

4. Information about relations to other database tables or fields. This is
especially useful when dealing with Inline elements or category selection fields.

Schema API can find out, which fields of other schemata are pointing to one-self.
Schema API differentiates between an "Active Relation" and a "Passive Relation".
An Active Relation is the information that a field such as "pages.media"
(a field of type "file") contains a reference to the "sys_file_reference.uid_foreign"
field. Active Relations in consequence are connected to a specific field
(of type :php:`RelationalFieldTypeInterface`).

In turn, a "Passive Relation" is the information what other schemata/fields are
pointing to a specific table or field.

A common example for a "Passive Relation" is "sys_workspace_stage":
The information stored in :php:`$GLOBALS[TCA][sys_workspace_stage]` does not contain
the information that this table is actually used as a reference from the database
field `sys_workspace.custom_stages`, the `sys_workspace_stage` Schema now
contains this information directly via :php:`TcaSchema->getPassiveRelations()`.
This is possible as TcaSchemaFactory is evaluating all TCA information and
holistically as a graph. Passive Relations
are currently only connected to a Schema, and Active Relations to a Field or
a Schema.

As the Schema API fetches information solely based on TCA, a Active Relation
only points to _possible_ references, however, the actual reference
(does a record really have a connection to another database table) would
require an actual Record instance (a database row) to evaluate this information.

Relations does not know about the "Type" or "Quantity" (many-to-many etc) as
this information is kept in the Field information already. For this reason,
the "Relations" currently only contain a flat information structure of the table
(and possibly a field) pointing TO another schema name (Active Relation) or
FROM another schema name / field (Passive Relation).

Schema API also parses all available FlexForm data structures in order to
resolve relations as well. As a result, a field of type FlexFormField contains
a list of possible "FlexFormSchema" instances, which resolve all fields, sheets
and section containers within each data structure.

5. Once built, the Schema can never be changed. Whereas with TCA was
possible to be overridden during runtime, all TCA is evaluated once and
then cached. This is a consequence on working with an object-oriented approach.

If TCA is changed after the Bootstrap process is completed,
the Schema needs to be rebuilt manually, which TYPO3 Core currently does for
example in some Functional Testing Scenarios.

All key objects (Schema, FieldType, Capabilities) are treated as immutable DTOs
and never contain cross-references to its parent objects (Sub schemata do not
know information about their parent schema, a field does not know which schema
it belongs to), so the only entry point is always the :php:`TcaSchemaFactory`
object.

This design allows the API fully cacheable on PHP level as a nested tree.

6. Low-level, not full-fletched but serves as a basis.

Several API decisions were made in order to let Schema API keep only its
original purpose, but can be encapsulated further in other APIs:

- Schema API is not available during Bootstrap as it needs TCA to be available
and fully finished.

- Schema API does not contain all available TCA properties for each field type.
An example is "renderType" for select fields. This information is not relevant
when querying records in the Frontend, and mainly relevant for FormEngine -
it is not generic enough to justify a getter method.

- Extensibility: Custom field types are currently not available for the time
being, until TYPO3 Core as fully migrated to Schema API.

- User Permissions: Evaluating if a user has access to "tt_content.bodytext"
requires information about the currently logged in user, thus not part of the
Schema API. A "Permission API" should rather evaluate this information in the
future.

- Available options for a field. As an example, a common scenario is to find out
which possible options are available for "pages.backend_layout". In TYPO3 Core
an :php:`itemsProcFunc` is connected to that field in TCA. Whether there is an
:php:`itemsProcFunc` is stored, but Schema API is not designed to actually
execute the itemsProcFunc as it is dependent on various factors evaluated during
runtime, such as the page it resides on, user permissions or PageTsConfig
overrides.

Schema API is currently marked as internal, as it might be changed during
TYPO3 v13 development, while more parts of TYPO3 will be migrated towards
Schema API.

DataHandler and the Record Factory already utilize Schema API in order to reduce
direct access to :php:`$GLOBALS[TCA]`.

Next to TCA and FlexForms, Schema API might also be used to evaluate information
for Site Configurations in the future.

Impact
======

Reading and writing :php:`$GLOBALS[TCA]` within :file:`Configuration/TCA/*`
and via TCA Overrides is untouched, as the API is meant for reading the
information there in a unified ways.

Usage
-----

The API can now be used to find out information about TCA fields.

.. code-block:: php

    public function __construct(
        protected readonly PageRepository $pageRepository,
        protected readonly TcaSchemaFactory $tcaSchemaFactory
    ) {}

    public function myMethod(string $tableName): void
    {
        if (!$this->tcaSchemaFactory->has($tableName)) {
            // this table is not managed via TYPO3's TCA API
            return;
        }
        $schema = $this->tcaSchemaFactory->get($tableName);

        // Find out if a table is localizable
        if ($schema->isLocalizable()) {
            // do something
        }

        // Find all registered types
        $types = $schema->getSubSchemata();

    }

Using the API improves handling for parts such as evaluating :php:`columnsOverrides`,
foreign field structures, FlexForm Schema parsing, and evaluating type fields
for a database field.

.. index:: PHP-API, TCA, ext:core
