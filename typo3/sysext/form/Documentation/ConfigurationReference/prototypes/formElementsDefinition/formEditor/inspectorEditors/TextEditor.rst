.. include:: ../../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.texteditor:

============
[TextEditor]
============

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.texteditor-introduction:

Introduction
============

Shows a single line textfield.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.texteditor-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.templatename-texteditor:

templateName
------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formEditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`

:aspect:`value`
      Inspector-TextEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.identifier-texteditor:
.. include:: properties/Identifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.label-texteditor:
.. include:: properties/Label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertypath-texteditor:
.. include:: properties/PropertyPath.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.donotsetifpropertyvalueisempty-texteditor:

doNotSetIfPropertyValueIsEmpty
------------------------------

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      If set to true then the property which should be written through this ``inspector editor`` will be removed within the ``form definition`` if the
      value from the ``inspector editor`` is empty instead of writing an empty value ('') for this property.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertyvalidators-texteditor:

propertyValidators
------------------

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Related options`
      - :ref:`"formElementPropertyValidatorsDefinition"<typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formelementpropertyvalidatorsdefinition>`

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      This ``inspector editors`` is able to validate it's value through JavaScript methods.
      This JavaScript validators can be registered through ``getFormEditorApp().addPropertyValidationValidator()``.
      The first method argument is the identifier for such a validator.
      Every array value within ``propertyValidators`` must be equal to such a identifier.

      For example:

     .. code-block:: yaml

         propertyValidators:
           10: 'Integer'
           20: 'FormElementIdentifierWithinCurlyBracesExclusive'


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertyvalidatorsmode-texteditor:

propertyValidatorsMode
----------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

.. :aspect:`Related options`
      @ToDo

:aspect:`Default value`
      AND

:aspect:`possible values`
      OR/ AND

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'OR' then at least one validator must be valid to accept the ``inspector editor`` value. If set to 'AND' then all validators must be valid.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.fieldexplanationtext-texteditor:

fieldExplanationText
--------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      A text which is shown at the bottom of the ``inspector editor``.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.additionalelementpropertypaths-texteditor:

additionalElementPropertyPaths
------------------------------

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      An array which holds property paths which should be written in addition to the propertyPath option.

      For example:

     .. code-block:: yaml

         additionalElementPropertyPaths:
           10: 'properties.fluidAdditionalAttributes.maxlength'
