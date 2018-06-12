.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.savetodatabase:

================
[SaveToDatabase]
================

.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinitionsavetodatabase-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         SaveToDatabase:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-frontendrendering-codecomponents-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.table:

options.table
-------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.table

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Insert or update values into this table.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.mode:

options.mode
------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.mode

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      'insert'

:aspect:`Possible values`
      insert/ update

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      ``insert`` will create a new database row with the values from the submitted form and/or some predefined values. @see options.elements and options.databaseFieldMappings

      ``update`` will update a given database row with the values from the submitted form and/or some predefined values. 'options.whereClause' is then required.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.whereclause:

options.whereClause
-------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.whereClause

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes, if mode = update

:aspect:`Default value`
      empty array

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      This where clause will be used for a database update action.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.elements:

options.elements
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.elements

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      empty array

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Use ``options.elements`` to map form element values to existing database columns.
      Each key within ``options.elements`` has to match with a form element identifier.
      The value for each key within ``options.elements`` is an array with additional informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.elements.<formelementidentifier>.mapondatabasecolumn:

options.elements.<formElementIdentifier>.mapOnDatabaseColumn
------------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.elements.<formElementIdentifier>.mapOnDatabaseColumn

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      The value from the submitted form element with the identifier ``<formElementIdentifier>`` will be written into this database column.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.elements.<formelementidentifier>.savefileidentifierinsteadofuid:

options.elements.<formElementIdentifier>.saveFileIdentifierInsteadOfUid
-------------------------------------------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.elements.<formElementIdentifier>.saveFileIdentifierInsteadOfUid

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Set this to true if the database column should not be written if the value from the submitted form element with the identifier
      ``<formElementIdentifier>`` is empty (think about password fields etc.).

      This setting only rules for form elements which creates a FAL object like ``FileUpload`` or ``ImageUpload``.
      By default, the uid of the FAL object will be written into the database column. Set this to true if you want to store the
      FAL identifier (1:/user_uploads/some_uploaded_pic.jpg) instead.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.elements.<formelementidentifier>.skipifvalueisempty:

options.elements.<formElementIdentifier>.skipIfValueIsEmpty
-------------------------------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.elements.<formElementIdentifier>.skipIfValueIsEmpty

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Set this to true if the database column should not be written if the value from the submitted form element with the identifier
      ``<formElementIdentifier>`` is empty (think about password fields etc.). Empty means strings without content, whitespace
      is valid content.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.elements.<formelementidentifier>.dateformat:

options.elements.<formElementIdentifier>.dateFormat
---------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.elements.<formElementIdentifier>.dateFormat

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      'U'

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      If the internal Datatype is \DateTime which is true for the form element types "DatePicker" and "Date",
      the object needs to be converted into a string value.
      This option allows you to define the format of the date.
      You can use every format accepted by PHP's date() function (http://php.net/manual/en/function.date.php#refsect1-function.date-parameters).
      The default value is "U" which means a Unix timestamp.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.databasecolumnmappings:

options.databaseColumnMappings
------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.databaseColumnMappings

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty array

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Use this to map database columns to static values.
      Each key within ``options.databaseColumnMappings`` has to match with an existing database column.
      The value for each key within ``options.databaseColumnMappings`` is an array with additional informations.

      This mapping is done *before* the ``options.element`` mapping.
      This means if you map a database column to a value through ``options.databaseColumnMappings`` and map a submitted
      form element value to the same database column through ``options.element``, the submitted form element value
      will override the value you set within ``options.databaseColumnMappings``.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.databasecolumnmappings.<databasecolumnname>.value:

options.databaseColumnMappings.<databaseColumnName>.value
---------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.databaseColumnMappings.<databaseColumnName>.value

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      The value which will be written to the database column.
      You can also use the :ref:`FormRuntime accessor feature<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>` to access every getable property from the ``FormRuntime``
      In short: use something like ``{<formElementIdentifier>}`` to get the value from the submitted form element with the identifier ``<formElementIdentifier>``.

      If you use the FormRuntime accessor feature within ``options.databaseColumnMappings``, than the functionality is nearly equal
      to the the ``options.elements`` configuration variant.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.databasecolumnmappings.<databasecolumnname>.skipifvalueisempty:

options.databaseColumnMappings.<databaseColumnName>.skipIfValueIsEmpty
----------------------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.databaseColumnMappings.<databaseColumnName>.skipIfValueIsEmpty

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Set this to true if the database column should not be written if the value from `options.databaseColumnMappings.
      <databaseColumnName>.value` is empty. Empty means strings without content, whitespace is valid content.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.options.translation.translationfile:

options.translation.translationFile
-----------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.options.translation.translationFile

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"SaveToDatabase finisher"<apireference-finisheroptions-savetodatabasefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         SaveToDatabase:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.SaveToDatabase.editor.header.label
             predefinedDefaults:
               options: {  }

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4

         SaveToDatabase:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.SaveToDatabase.editor.header.label
             predefinedDefaults:
               options: {  }

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.savetodatabase.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.SaveToDatabase.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 5-

         SaveToDatabase:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.SaveToDatabase.editor.header.label
             predefinedDefaults:
               options: {  }

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
