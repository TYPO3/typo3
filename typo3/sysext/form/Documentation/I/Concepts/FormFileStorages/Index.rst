.. include:: /Includes.rst.txt


.. _concepts-form-file-storages:

Form/ File storage
==================

Form definitions can also be stored in and shipped with your own
extensions and backend users can then
embed your forms. Furthermore, you can configure that your form
definitions:

- can be edited in the ``form editor``,
- can be deleted with the ``form manager``.

By default, all these options are turned off because dynamic content inside an
extension - possibly version-controlled - is not a good idea. There is also no
ACL system available.

**File uploads** are saved in file mounts. They are handled
as FAL objects. The file mounts for file uploads can be configured.
When adding/ editing a file upload element, backend users can select the
storage for the uploads.

Add your extension path as an additional file mount for form definitions as follows:

.. code-block:: yaml

   persistenceManager:
     allowedExtensionPaths:
       10: EXT:my_site_package/Resources/Private/Forms/

Allow backend users to **edit** forms stored in your extension as follows:

.. code-block:: yaml

   persistenceManager:
     allowSaveToExtensionPaths: true

Allow backend users to **delete** forms stored in your extension as follows:

.. code-block:: yaml

   persistenceManager:
     allowDeleteFromExtensionPaths: true

The following YAML shows the default file mount setup for file (and image) uploads.

.. code-block:: yaml

   prototypes:
     standard:
       formElementsDefinition:
         FileUpload:
           formEditor:
             predefinedDefaults:
               properties:
                 saveToFileMount: '1:/user_upload/'
             editors:
               400:
                 selectOptions:
                   10:
                     value: '1:/user_upload/'
                     label: '1:/user_upload/'
           properties:
             saveToFileMount: '1:/user_upload/'
         ImageUpload:
           formEditor:
             predefinedDefaults:
               properties:
                 saveToFileMount: '1:/user_upload/'
             editors:
               400:
                 selectOptions:
                   10:
                     value: '1:/user_upload/'
                     label: '1:/user_upload/'
