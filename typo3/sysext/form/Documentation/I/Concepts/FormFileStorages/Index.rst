.. include:: /Includes.rst.txt


.. _concepts-form-file-storages:

Form storage
============

Form definitions are stored in the database, in file mounts, or in
extensions. Editors select the storage in the
:ref:`Storage step of the form wizard <concepts-formmanager-storage>`.

..  _concepts-form-storages-database:

Database storage
----------------

..  versionadded:: 14.2
    :changelog: feature-108653-1767199420

The database storage saves form definitions as records in the table
`form_definition`. The records are stored on the root page (page ID 0).
The persistence identifier of such a form is the UID of its record.

Grant backend user groups access to the table `form_definition` in the
field **Table permissions**:

*   :guilabel:`Read` lets users see the forms.
*   :guilabel:`Read & Write` lets users create, edit, and delete forms.

All backend users with access to the table see all forms in the database.
The database storage cannot restrict a user group to a subset of the
forms.

The records are read-only in the :guilabel:`Content > Records` module.
Clicking their title opens the form editor. Editors delete the forms in the
`form manager`. The action :guilabel:`Show History` of the form manager
shows the record history of a form.

..  _concepts-form-storages-filemount:

File mount storage
------------------

..  deprecated:: 14.2
    :changelog: deprecation-108653-1741600000

    The file mount storage will be removed in TYPO3 v15.0. Move the forms
    to the database with the upgrade wizard
    :guilabel:`Migrate file-based forms to database storage` or with the
    command :ref:`form:definition:transfer <concepts-form-storages-transfer>`.

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

..  _concepts-form-storages-extension:

Extension storage
-----------------

Form definitions can also be stored in and shipped with your own
extensions and backend users can then
embed your forms. Furthermore, you can configure that your form
definitions:

- can be edited in the ``form editor``,
- can be deleted with the ``form manager``.

By default, all these options are turned off because dynamic content inside an
extension - possibly version-controlled - is not a good idea. There is also no
ACL system available.

Add a folder of your extension for form definitions as follows:

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

..  _concepts-form-storages-transfer:

Transfer forms between storages
-------------------------------

The console command `form:definition:transfer` copies form definitions
from one storage to another. Use it, for example, to move the forms of an
extension into the database:

..  code-block:: bash

    vendor/bin/typo3 form:definition:transfer --source=extension --target=database --move

The command has these options:

`--source`
    The storage to read the forms from: `database`, `filemount`, or
    `extension`.

`--target`
    The storage to write the forms to: `database`, `filemount`, or
    `extension`.

`--target-location`
    The location in the target storage. For `database`, the location is
    always `0`. For `extension`, use a path from
    :ref:`allowedExtensionPaths <persistencemanager.allowedExtensionPaths>`.
    For `filemount`, use a folder from
    :ref:`allowedFileMounts <persistencemanager.allowedfilemounts>`.

`--form-identifier`
    Transfers only the form with this identifier.

`--move`
    Deletes the form in the source storage after the transfer.

`--dry-run`
    Lists the forms to transfer without changing anything.

The command updates the references in content elements of the table
`tt_content`. It does not update references in other tables.

..  _concepts-form-storages-uploads:

File uploads
------------

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
