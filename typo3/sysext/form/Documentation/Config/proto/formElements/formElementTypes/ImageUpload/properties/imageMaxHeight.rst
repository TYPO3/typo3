properties.imageMaxHeight
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.ImageUpload.properties.imageMaxHeight

:aspect:`Data type`
      int

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
         :emphasize-lines: 13

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
      The max height for the uploaded image preview.