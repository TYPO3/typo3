.. include:: ../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formengine:

============
[formEngine]
============


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formengine-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formengine.translationfile:

translationFile
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formEngine.translationFile

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
           translationFile: 'EXT:form/Resources/Private/Language/Database.xlf'

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for form plugin translations.
