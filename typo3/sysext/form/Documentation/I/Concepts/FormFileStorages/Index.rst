.. include:: /Includes.rst.txt


.. _concepts-form-file-storages:

Form storage
============

Form definitions are stored in the database or in extensions. Editors
select the storage in the
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
    The storage to read the forms from: `database` or `extension`.

`--target`
    The storage to write the forms to: `database` or `extension`.

`--target-location`
    The location in the target storage. For `database`, the location is
    always `0`. For `extension`, use a path from
    :ref:`allowedExtensionPaths <persistencemanager.allowedExtensionPaths>`.

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
