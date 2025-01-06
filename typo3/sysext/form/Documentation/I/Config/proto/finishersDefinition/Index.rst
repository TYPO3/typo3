.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition:

=====================
[finishersDefinition]
=====================


.. _prototypes.prototypeIdentifier.finishersdefinition-properties:

Properties
==========


.. _prototypes.prototypeIdentifier.finishersdefinition-properties-finishersdefinition:

[finishersDefinition]
---------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         prototypes:
           <prototypeIdentifier>:
             finishersDefinition:
               [...]

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      Array which defines the available finishers. Every key within this array is called the ``<finisherIdentifier>``.


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier:

<finisherIdentifier>
--------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersdefinition.<finisherIdentifier>

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

.. :aspect:`Related options`
      @ToDo

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:

         prototypes:
           standard:
             Closure:
               [...]
             Confirmation:
               [...]
             EmailToSender:
               [...]
             EmailToReceiver:
               [...]
             DeleteUploads:
               [...]
             FlashMessage:
               [...]
             Redirect:
               [...]
             SaveToDatabase:
               [...]

:aspect:`Related options`
      - :ref:`"prototypes.prototypeIdentifier.formElementsDefinition.formelementtypeidentifier.formEditor.propertyCollections.finishers.[*].identifier"<prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections.finishers.*.identifier>`
      - :ref:`"[FinishersEditor] selectOptions.[*].value"<prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.selectoptions.*.value-finisherseditor>`

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      This array key identifies a finisher. This identifier could be used to attach a finisher to a form.


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-commonproperties:

Common <finisherIdentifier> properties
=============================================


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeidentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      .. include:: properties/implementationClassName.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.options:

options
-------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.options

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      Array with finisher options.


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.translation.translationFiles:

translation.translationFiles
----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.translation.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for finisher translations.
      If the property is undefined, - :ref:`"prototypes.prototypeIdentifier.formElementsDefinition.Form.renderingOptions.translation.translationFiles"<prototypes.prototypeIdentifier.formelementsdefinition.form.renderingoptions.translation.translationfiles>` will be used.


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.formeditor:

formEditor
----------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Array with configurations for the ``form editor``


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
       .. include:: properties/iconIdentifier.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: properties/label.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: properties/predefinedDefaults.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.formengine:

FormEngine
----------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.FormEngine

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Array with configurations for the ``form plugin``


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.FormEngine.label:

FormEngine.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.FormEngine.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: properties/formEngine/label.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.FormEngine.elements:

FormEngine.elements
-------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.FormEngine.elements

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: properties/formEngine/elements.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier-concreteconfigurations:

Concrete configurations
=======================

.. toctree::

    finishers/Closure
    finishers/Confirmation
    finishers/EmailToReceiver
    finishers/EmailToSender
    finishers/DeleteUploads
    finishers/FlashMessage
    finishers/Redirect
    finishers/SaveToDatabase
