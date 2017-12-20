.. include:: ../../Includes.txt

==================================================================
Important: #82328 - EXT:form - use own folder for form definitions
==================================================================

See :issue:`82328`

Description
===========

Change default filemount for form definitions to :file:`fileadmin/form_definitions`,
the directory is automatically created if necessary.

Existing forms in :file:`fileadmin/user_upload` can be listed, duplicated and removed
but not edited.

Since no migration wizard can be provided for this, a manual migration is
necessary which can be done

* by moving all form definitions to the new directory via filesystem or
* by duplicating all form definitions (thus storing them in the new location) and removing the old form definitions.

.. index:: Backend, ext:form
