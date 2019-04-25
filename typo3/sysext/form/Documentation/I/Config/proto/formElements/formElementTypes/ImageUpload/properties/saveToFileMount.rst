properties.saveToFileMount
--------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.ImageUpload.properties.saveToFileMount

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      Yes

:aspect:`Mandatory`
      No

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 6

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
      The location (file mount) for the uploaded images.
      If this file mount or the property "saveToFileMount" does not exist
      the folder in which the form definition lies (persistence identifier) will be used.
      If the form is generated programmatically and therefore no persistence identifier exist
      the default storage "1:/user_upload/" will be used.
