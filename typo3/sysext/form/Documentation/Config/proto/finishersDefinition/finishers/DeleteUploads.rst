.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.deleteuploads:

===============
[DeleteUploads]
===============

.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinitiondeleteuploads-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.deleteuploads.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.DeleteUploads.implementationClassName

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

         DeleteUploads:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\DeleteUploadsFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      Array which defines the available finishers. Every key within this array is called the ``<finisherIdentifier>``


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.deleteuploads.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.DeleteUploads.formEditor.iconIdentifier

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

         DeleteUploads:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.DeleteUploads.editor.header.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.deleteuploads.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.DeleteUploads.formEditor.label

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

         DeleteUploads:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.DeleteUploads.editor.header.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst

