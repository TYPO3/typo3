.. include:: /Includes.rst.txt


.. _prototypes:

============
[prototypes]
============


.. _prototypes-properties:

Properties
==========

.. _prototypes.*:

prototypes
----------

:aspect:`Option path`
      prototypes

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


.. _prototypes.<prototypeidentifier>:

<prototypeIdentifier>
---------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form manager/ form editor/ plugin)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`"formManager.selectablePrototypesConfiguration.*.identifier"<formmanager.selectableprototypesconfiguration.*.identifier>`

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
