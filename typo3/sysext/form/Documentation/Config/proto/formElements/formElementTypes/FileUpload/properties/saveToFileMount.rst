properties.saveToFileMount
--------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.FileUpload.properties.saveToFileMount

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

         FileUpload:
           properties:
             containerClassAttribute: input
             elementClassAttribute: ''
             elementErrorClassAttribute: error
             saveToFileMount: '1:/user_upload/'
             allowedMimeTypes:
               - application/msword
               - application/vnd.openxmlformats-officedocument.wordprocessingml.document
               - application/vnd.oasis.opendocument.text
               - application/pdf

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The location (file mount) for the uploaded files.
      If this file mount or the property "saveToFileMount" does not exist
      the folder in which the form definition lies (persistence identifier) will be used.
      If the form is generated programmatically and therefore no persistence identifier exist
      the default storage "1:/user_upload/" will be used.
