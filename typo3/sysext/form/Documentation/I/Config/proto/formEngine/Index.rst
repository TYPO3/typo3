.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.formengine:

============
[formEngine]
============


.. _prototypes.prototypeIdentifier.formengine-properties:

Properties
==========

.. _prototypes.prototypeIdentifier.formengine.translationfiles:

translationFiles
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formEngine.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         formEngine:
           translationFiles:
             10: 'EXT:form/Resources/Private/Language/Database.xlf'

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for form plugin translations.
