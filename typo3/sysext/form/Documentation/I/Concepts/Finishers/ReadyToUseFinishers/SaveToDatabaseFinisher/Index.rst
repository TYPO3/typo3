..  include:: /Includes.rst.txt
..  _concepts-finishers-savetodatabasefinisher:

=======================
SaveToDatabase finisher
=======================

The "SaveToDatabase finisher" saves the data of a submitted form into a
database table.

..  contents:: Table of contents

..  note::

    This finisher cannot be used from the backend editor. It can only be
    inserted directly into the YAML form definition or programmatically.

..  include:: /Includes/_NoteFinisher.rst

..  _apireference-finisheroptions-savetodatabasefinisher-options:

Options of the SaveToDatabase finisher
======================================

The following options can be set directly in the form definition YAML or
programmatically in the options array:

..  _apireference-finisheroptions-savetodatabasefinisher-options-table:

..  confval:: table
    :name: savetodatabasefinisher-table
    :type: string
    :required: true

    Insert or update values into this table.

..  _apireference-finisheroptions-savetodatabasefinisher-options-mode:

..  confval:: mode
    :name: savetodatabasefinisher-mode
    :type: string
    :default: `'insert'`

    `insert`
        will create a new database row with the values from the submitted form
        and/or some predefined values. See also :confval:`savetodatabasefinisher-elements` and
        :confval:`savetodatabasefinisher-databaseColumnMappings`.

    `update`
        will update a given database row with the values from the submitted form
        and/or some predefined values. In this case :confval:`savetodatabasefinisher-whereClause` is required.

..  _apireference-finisheroptions-savetodatabasefinisher-options-whereclause:

..  confval:: whereClause
    :name: savetodatabasefinisher-whereClause
    :type: array
    :required: true (if mode = update)
    :default: `[]`

    This where clause will be used for a database update action.

..  _apireference-finisheroptions-savetodatabasefinisher-options-elements:

..  confval:: elements
    :name: savetodatabasefinisher-elements
    :type: array
    :required: true

    Use `options.elements` to map form element values to existing database columns.
    Each key within `options.elements` has to match with a form element identifier.
    The value for each key within `options.elements` is an array with additional information.

..  _apireference-finisheroptions-savetodatabasefinisher-options-elements-mapondatabasecolumn:

..  confval:: elements.<formElementIdentifier>.mapOnDatabaseColumn
    :name: savetodatabasefinisher-elements-mapOnDatabaseColumn
    :type: string
    :required: true

    The value from the submitted form element with the identifier
    `<formElementIdentifier>` will be written into this database column.

..  _apireference-finisheroptions-savetodatabasefinisher-options-elements-skipifvalueisempty:

..  confval:: elements.<formElementIdentifier>.skipIfValueIsEmpty
    :name: savetodatabasefinisher-elements-skipIfValueIsEmpty
    :type: bool
    :default: `false`

    Set this to true if the database column should not be written if the value from the
    submitted form element with the identifier `<formElementIdentifier>` is empty
    (e.g. for password fields). Empty means strings without content, whitespace is valid content.

..  _apireference-finisheroptions-savetodatabasefinisher-options-elements-hashed:

..  confval:: elements.<formElementIdentifier>.hashed
    :name: savetodatabasefinisher-elements-hashed
    :type: bool
    :default: `false`

    Set this to true if the value from the submitted form element should be hashed before
    writing into the database.

..  _apireference-finisheroptions-savetodatabasefinisher-options-elements-savefileidentifierinsteadofuid:

..  confval:: elements.<formElementIdentifier>.saveFileIdentifierInsteadOfUid
    :name: savetodatabasefinisher-elements-saveFileIdentifierInsteadOfUid
    :type: bool
    :default: `false`

    By default, the uid of the FAL object will be written into the database column.
    Set this to true if you want to store the FAL identifier
    (e.g. `1:/user_uploads/some_uploaded_pic.jpg`) instead.

    This only applies for form elements which create a FAL object like
    `FileUpload` or `ImageUpload`.

..  _apireference-finisheroptions-savetodatabasefinisher-options-elements-dateformat:

..  confval:: elements.<formElementIdentifier>.dateFormat
    :name: savetodatabasefinisher-elements-dateFormat
    :type: string
    :default: `'U'`

    If the internal datatype is :php:`\DateTime` (true for the form element types
    :yaml:`DatePicker` and :yaml:`Date`), the object needs to be converted into a string.
    This option defines the format of the date. You can use any format accepted by
    the PHP :php:`date()` function.
    Default is `'U'` (Unix timestamp).

..  _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings:

..  confval:: databaseColumnMappings
    :name: savetodatabasefinisher-databaseColumnMappings
    :type: array
    :default: `[]`

    Use this to map database columns to static values.
    Each key within `options.databaseColumnMappings` has to match an existing database column.
    The value for each key within `options.databaseColumnMappings` is an array with
    additional information.

    This mapping is done *before* the :confval:`savetodatabasefinisher-elements` mapping.
    If you map both, the value from :confval:`savetodatabasefinisher-elements` will override the
    :confval:`savetodatabasefinisher-databaseColumnMappings-value` value.

..  _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings-value:

..  confval:: databaseColumnMappings.<databaseColumnName>.value
    :name: savetodatabasefinisher-databaseColumnMappings-value
    :type: string
    :required: true

    The value which will be written to the database column.
    You can also use the :ref:`FormRuntime accessor feature
    <concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
    to access properties from the `FormRuntime`, e.g. `{<formElementIdentifier>}`.

..  _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings-skipifvalueisempty:

..  confval:: databaseColumnMappings.<databaseColumnName>.skipIfValueIsEmpty
    :name: savetodatabasefinisher-databaseColumnMappings-skipIfValueIsEmpty
    :type: bool
    :default: `false`

    Set this to true if the database column should not be written if the value from
    :confval:`savetodatabasefinisher-databaseColumnMappings-value` is empty.

..  _concepts-finishers-savetodatabasefinisher-yaml:

SaveToDatabase finisher in the YAML form definition
===================================================

This finisher saves the data from a submitted form into a database table.

..  literalinclude:: _codesnippets/_form.yaml
    :linenos:
    :caption: public/fileadmin/forms/my_form.yaml

..  _concepts-finishers-savetodatabasefinisher-example-news:

Example for adding uploads to ext:news (fal_related_files and fal_media):
=========================================================================

..  literalinclude:: _codesnippets/_example-fal-uploads_news.yaml
    :linenos:
    :caption: public/fileadmin/forms/my_form_with_multiple_finishers.yaml

..  _apireference-finisheroptions-savetodatabasefinisher:

Usage of the SaveToDatabase finisher in PHP code
================================================

Developers can create a confirmation finisher by using the key `SaveToDatabase`:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php
    :linenos:

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher`.

..  _concepts-finishers-savetodatabasefinisher-multiple:

Multiple database operations
============================

You can write options as an array to perform multiple database operations.

Usage within form definition.

..  literalinclude:: _codesnippets/_example-fal-uploads_news.yaml
    :linenos:
    :caption: public/fileadmin/forms/my_form_with_multiple_finishers.yaml

Usage through code:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php
    :linenos:

This performs 2 database operations.

One insert and one update.

You can access the inserted UIDs through '{SaveToDatabase.insertedUids.<theArrayKeyNumberWithinOptions>}'
If you perform an insert operation, the value of the inserted database row will be stored within the FinisherVariableProvider.
<theArrayKeyNumberWithinOptions> references to the numeric options.* key.
