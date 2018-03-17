.. include:: ../../Includes.txt


.. _concepts-form-file-storages:

Form/ File storages
===================

EXT:form stores the form definitions within the file system and thus needs
write access to this storage. By default, the filemount ``user_uploads`` is
used. It is possible to configure a different and/ or an additional
filemount, which is then utilized for storing and reading forms.

The backend user will only see form definitions that are stored in
filemounts where the user has at least read access. The ``form editor`` and
the ``form plugin`` respect those access rights. In this way, you are able
to implement ACLs. If you have configure more than one filemount and the
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

**File uploads** will be saved within filemounts as well. They are handled
as FAL objects. The available filemounts for such uploads can be configured.
When adding/ editing a file upload element, the backend user can select the
desired upload storage.

.. note::

   In principle, files in filemounts are publicly accessible. If the
   uploaded files could contain sensitive data, you should suppress any
   HTTP access to the filemount. This may, for example, be achieved by
   creating a .htaccess file, assuming you are using an Apache web server.
   The directive of the .htaccess file is fairly easy:

   .. code-block:: html

      Order deny,allow
      Deny from all

The following code block shows you how to configure additional filemounts
for form definitions.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         persistenceManager:
           allowedFileMounts:
             # default filemount, no need to redeclare it again
             # just to show you the structure
             # 10: 1:/form_definitions/
             # additional filemounts
             100: 1:/custom/forms/
             110: 2:/cloudstorage/forms/

The following code block shows you how to allow an extension path as an
additional filemount for form definitions.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         persistenceManager:
           allowedExtensionPaths:
             10: EXT:my_site_package/Resources/Private/Forms/

Add the following config if you want to allow backend users to **edit**
forms stored within your own extension.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         persistenceManager:
           allowSaveToExtensionPaths: true

Add the following config if you want to allow backend users to **delete**
forms stored within your own extension.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         persistenceManager:
           allowDeleteFromExtensionPaths: true

The following code blocks show you the default setup for filemounts that
are used for file (and image) uploads.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
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
               ImageUpload
                 properties:
                   saveToFileMount: '1:/user_upload/'
                  editors:
                     400:
                       selectOptions:
                         10:
                           value: '1:/user_upload/'
                           label: '1:/user_upload/'
