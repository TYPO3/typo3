.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition:

=====================
[finishersDefinition]
=====================


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.*:

[finishersDefinition]
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition

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


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.<finisheridentifier>:

<finisherIdentifier>
--------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisherIdentifier>

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
      - :ref:`"TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formElementsDefinition.\<formElementTypeIdentifier>.formEditor.propertyCollections.finishers.[*].identifier"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.propertycollections.finishers.*.identifier>`
      - :ref:`"[FinishersEditor] selectOptions.[*].value"<typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.selectoptions.*.value-finisherseditor>`

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      This array key identifies a finisher. This identifier could be used to attach a finisher to a form.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.<finisheridentifier>-commonproperties:

Common <finisherIdentifier> properties
=============================================


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      .. include:: properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.options:

options
-------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.options

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      Array with finisher options.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.translation.translationFiles:

translation.translationFiles
----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.translation.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for finisher translations.
      If the property is undefined, - :ref:`"TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formElementsDefinition.Form.renderingOptions.translation.translationFiles"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.form.renderingoptions.translation.translationfiles>` will be used.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.formeditor:

formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Array with configurations for the ``form editor``


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
       .. include:: properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: properties/predefinedDefaults.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.formengine:

FormEngine
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.FormEngine

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Array with configurations for the ``form plugin``


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.FormEngine.label:

FormEngine.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.FormEngine.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: properties/formEngine/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.<finisheridentifier>.FormEngine.elements:

FormEngine.elements
-------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.FormEngine.elements

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete finishers configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: properties/formEngine/elements.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.<finisheridentifier>-concreteconfigurations:

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
