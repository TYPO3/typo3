.. include:: /Includes.rst.txt
properties.contentElementUid
----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.ContentElement.properties.contentElementUid

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      Yes

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         ContentElement:
           properties:
             contentElementUid: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The uid of the content element which should be rendered.
