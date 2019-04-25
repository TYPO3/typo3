properties.elementErrorClassAttribute
-------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.ImageUpload.properties.elementErrorClassAttribute

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      No

:aspect:`Mandatory`
      No

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 5

         ImageUpload:
           properties:
             containerClassAttribute: input
             elementClassAttribute: lightbox
             elementErrorClassAttribute: error
             saveToFileMount: '1:/user_upload/'
             allowedMimeTypes:
               - image/jpeg
               - image/png
               - image/bmp
             imageLinkMaxWidth: 500
             imageMaxWidth: 500
             imageMaxHeight: 500

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      A CSS class which is written to the form element if validation errors exists.