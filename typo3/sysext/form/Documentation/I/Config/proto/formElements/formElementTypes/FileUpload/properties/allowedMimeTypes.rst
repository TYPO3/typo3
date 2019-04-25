properties.allowedMimeTypes
---------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.FileUpload.properties.allowedMimeTypes

:aspect:`Data type`
      array

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
         :emphasize-lines: 7-

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
      The allowed mime types for the file uploads.