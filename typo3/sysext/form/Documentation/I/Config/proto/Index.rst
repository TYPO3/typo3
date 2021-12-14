.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes:

============
[prototypes]
============


.. _typo3.cms.form.prototypes-properties:

Properties
==========

.. _typo3.cms.form.prototypes.*:

prototypes
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form manager/ form editor/ plugin)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         prototypes:
           standard:
             [...]

:aspect:`Good to know`
      - :ref:`"Prototypes"<concepts-configuration-prototypes>`
      - :ref:`"Form configuration vs. form definition"<concepts-formdefinition-vs-formconfiguration>`

:aspect:`Description`
      Array which defines the available prototypes. Every key within this array is called the ``<prototypeIdentifier>``.


.. _typo3.cms.form.prototypes.<prototypeidentifier>:

<prototypeIdentifier>
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form manager/ form editor/ plugin)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`"TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration.*.identifier"<typo3.cms.form.formmanager.selectableprototypesconfiguration.*.identifier>`

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         prototypes:
           standard:
             [...]

:aspect:`Good to know`
      - :ref:`"Prototypes"<concepts-configuration-prototypes>`
      - :ref:`"Form configuration vs. form definition"<concepts-formdefinition-vs-formconfiguration>`

:aspect:`Description`
      This array key identifies the `prototype``. Every ``form definition`` references to such a ``<prototypeIdentifier>`` through the property ``prototypeName``.


Subproperties
=============

.. toctree::

   formElements/Index
   finishersDefinition/Index
   validatorsDefinition/Index
   formEditor/Index
   formEngine/Index
