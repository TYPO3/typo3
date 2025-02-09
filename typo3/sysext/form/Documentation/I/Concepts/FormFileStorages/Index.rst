.. include:: /Includes.rst.txt


.. _concepts-form-file-storages:

Form/ File storages
===================

EXT:form stores the form definitions within the file system (FAL) and thus needs
write access to this storage. By default, the file mount ``form_definitions`` is
used. It is possible to configure a different and/ or an additional
file mount, which is then utilized for storing and reading forms.

The backend user will only see form definitions that are stored in
file mounts where the user has at least read access. The ``form editor`` and
the ``form plugin`` respect those access rights. In this way, you are able
to implement ACLs. If you have configured more than one file mount and the
backend user is able to access those, the ``form manager`` will allow the
user to choose the preferred storage in which the form will be saved.

Even cooler, form definitions can be stored in and shipped with your custom
extensions. If configured accordingly, the backend user will be able to
embed those forms. Furthermore, you can configure that these form
definitions:

- can be edited within the ``form editor``,
- can be deleted with the help of the ``form manager``.

By default, the aforementioned options are turned off. We decided to do so
because having dynamic content within an extension - which is possibly
version-controlled - is usually not a good idea. Furthermore, there is no
ACL system available.

**File uploads** will be saved within file mounts as well. They are handled
as FAL objects. The available file mounts for such uploads can be configured.
When adding/ editing a file upload element, the backend user can select the
desired upload storage.

.. note::

   In principle, files in file mounts are publicly accessible. If the
   uploaded files could contain sensitive data, you should suppress any
   HTTP access to the file mount. This may, for example, be achieved by
   creating a :file:`.htaccess` file, assuming you are using an Apache web
   server. The directive of the :file:`.htaccess` file is fairly easy:

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

The following code block shows you how to configure additional file mounts
for form definitions.

.. code-block:: yaml

   persistenceManager:
     allowedFileMounts:
       # default file mount, no need to redeclare it again
       # just to show you the structure
       # 10: 1:/form_definitions/
       # additional file mounts
       100: 1:/custom/forms/
       110: 2:/cloudstorage/forms/

The following code block shows you how to allow an extension path as an
additional file mount for form definitions.

.. code-block:: yaml

   persistenceManager:
     allowedExtensionPaths:
       10: EXT:my_site_package/Resources/Private/Forms/

Add the following config if you want to allow backend users to **edit**
forms stored within your own extension.

.. code-block:: yaml

   persistenceManager:
     allowSaveToExtensionPaths: true

Add the following config if you want to allow backend users to **delete**
forms stored within your own extension.

.. code-block:: yaml

   persistenceManager:
     allowDeleteFromExtensionPaths: true

The following code blocks show you the default setup for file mounts that
are used for file (and image) uploads.

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
