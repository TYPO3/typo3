.. include:: /Includes.rst.txt


.. _persistencemanager:

====================
[persistenceManager]
====================


.. _persistencemanager-properties:

Properties
==========

.. _persistencemanager.allowedfilemounts:

allowedFileMounts
-----------------

:aspect:`Option path`
      persistenceManager.allowedFileMounts

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form manager/ form editor/ plugin)

:aspect:`Mandatory`
      Yes (if :ref:`allowedExtensionPaths <persistenceManager.allowedExtensionPaths>` is not set)

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         persistenceManager:
           allowedFileMounts:
             10: '1:/form_definitions/'

:aspect:`Good to know`
      :ref:`Form/ File storages<concepts-form-file-storages>`

:aspect:`Description`
      EXT:form stores the form definitions within the file system and thus needs
      write access to this storage. By default, the folder ``form_definitions`` is
      created and used. It is possible to configure a different and/ or an additional
      file mount which is then utilized for storing and reading forms.


.. _persistencemanager.allowSaveToExtensionPaths:

allowSaveToExtensionPaths
-------------------------

:aspect:`Option path`
      persistenceManager.allowSaveToExtensionPaths

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         persistenceManager:
           allowSaveToExtensionPaths: false

:aspect:`Good to know`
      :ref:`Form/ File storages<concepts-form-file-storages>`

:aspect:`Description`
      Set this to ``true`` if you want to allow backend users to **edit** forms stored within your own extension.


.. _persistencemanager.allowDeleteFromExtensionPaths:

allowDeleteFromExtensionPaths
-----------------------------

:aspect:`Option path`
      persistenceManager.allowDeleteFromExtensionPaths

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         persistenceManager:
           allowDeleteFromExtensionPaths: false

:aspect:`Good to know`
      :ref:`Form/ File storages<concepts-form-file-storages>`

:aspect:`Description`
      Set this to ``true`` if you want to allow backend users to **delete** forms stored within your own extension.


.. _persistencemanager.sortByKeys:

sortByKeys
-----------------------------

:aspect:`Option path`
      persistenceManager.sortByKeys

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml

          persistenceManager:
             sortByKeys: ['name', 'fileUid']

:aspect:`Good to know`
      :ref:`Form/ File storages<concepts-form-file-storages>`

:aspect:`Description`
      The keys by which the forms should be sorted in the Form module and in the form plugin select.

      Valid keys, by which the forms can be sorted, are:

      ``name``
         The forms name.

      ``identifier``
         The filename.

      ``fileUid``
         The files uid.

      ``persistenceIdentifier``
         The files location.

         Example: ``1:/form_definitions/contact.form.yaml``

      ``readOnly``
         Is the form readonly?

      ``removable``
         Is the form removable?

      ``location``
         Either `storage` or `extension`

      ``invalid``
         Does the form have an error?


.. _persistencemanager.sortAscending:

sortAscending
-----------------------------

:aspect:`Option path`
      persistenceManager.sortAscending

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml

         persistenceManager:
           sortAscending: true

:aspect:`Good to know`
      :ref:`Form/ File storages<concepts-form-file-storages>`

:aspect:`Description`
      If set to ``true``, the forms will be sorted in ascending, otherwise in descending order.


.. _persistencemanager.allowedExtensionPaths:

allowedExtensionPaths
---------------------

:aspect:`Option path`
      persistenceManager.allowedExtensionPaths

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form manager/ form editor/ plugin)

:aspect:`Mandatory`
      Yes (if :ref:`allowedFileMounts <persistenceManager.allowedFileMounts>` is not set)

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      :ref:`Form/ File storages<concepts-form-file-storages>`

:aspect:`Description`
      Define the paths to folders which contain forms within your own extension.
      For example:

      .. code-block:: yaml

            allowedExtensionPaths:
              10: EXT:my_site_package/Resources/Private/Forms/
