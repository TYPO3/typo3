.. include:: /Includes.rst.txt


.. _concepts-form-file-storages:

Form/ File storage
==================

Form definitions are stored in the file system (FAL) so EXT:form needs
write access to the file system. Form definitions are stored in the
``form_definitions`` file mount by default. You can configure a different and/ or an additional
file mount for storing and reading form definitions.

A backend user will only see form definitions that are stored in
file mounts where they have at least read access. The ``form editor`` and
``form plugin`` respect these access rights, meaning you can
implement ACLs. If you have configured more than one file mount and a
backend user has access, the ``form manager`` will allow the
user to choose their preferred storage.

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

.. note::

   In principle, files in file mounts are publicly accessible. If the
   uploaded files could contain sensitive data, you should suppress any
   HTTP access to the file mount. You could do this by
   creating a :file:`.htaccess` file if you are using an Apache web
   server. The :file:`.htaccess` file directive is as follows:

   .. code-block:: apache

      # Apache ≥ 2.3
      <IfModule mod_authz_core.c>
         Require all denied
      </IfModule>

      # Apache < 2.3
      <IfModule !mod_authz_core.c>
         Order allow,deny
         Deny from all
         Satisfy All
      </IfModule>

Configure additional file mounts for form definitions as follows:

.. code-block:: yaml

   persistenceManager:
     allowedFileMounts:
       # default file mount, no need to redeclare it again
       # just to show you the structure
       # 10: 1:/form_definitions/
       # additional file mounts
       100: 1:/custom/forms/
       110: 2:/cloudstorage/forms/

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
