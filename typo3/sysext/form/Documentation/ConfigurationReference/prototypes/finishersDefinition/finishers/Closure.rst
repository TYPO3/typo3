.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.closure:

=========
[Closure]
=========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinitionclosure-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.closure.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Closure.implementationClassName

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

         Closure:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-frontendrendering-codecomponents-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.closure.options.closure:

options.closure
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Closure.options.closure

:aspect:`Data type`
      \Closure

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

.. :aspect:`Good to know`
      ToDo
      - :ref:`"Closure finisher"<apireference-finisheroptions-closurefinisher>`
      - :ref:`"Custom finisher implementations"<concepts-frontendrendering-codecomponents-customfinisherimplementations>`

:aspect:`Description`
      The closure which are invoked if the finisher is treggered.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.closure.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Closure.formEditor.iconIdentifier

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

         Closure:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.Closure.editor.header.label
             predefinedDefaults:
               options:
                 closure: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.closure.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Closure.formEditor.label

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

         Closure:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.Closure.editor.header.label
             predefinedDefaults:
               options:
                 closure: ''

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.closure.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Closure.formEditor.predefinedDefaults

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

         Closure:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.Closure.editor.header.label
             predefinedDefaults:
               options:
                 closure: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
