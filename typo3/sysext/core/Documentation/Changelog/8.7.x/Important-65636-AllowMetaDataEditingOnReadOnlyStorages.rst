.. include:: ../../Includes.txt

==========================================================================
Important: #65636 - File meta data can now be edited on read only storages
==========================================================================

See :issue:`65636`

Description
===========

Whether meta data editing of files is allowed or not, must not be bound to whether a file is
physically writable in a storage, or whether the storage itself is set read only.

Editing meta data should on the other hand be forbidden, when the file is within a read only
file mount.

Allowing meta data editing on read only storage
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

To allow to distinguish between read access to a file and write access to file meta data,
a new file action permission `editMeta` is introduced, which is automatically checked and
enforced when saving a meta data record.

When having to check for editing meta data permission in userland code, it is recommended
to use the new file action permission instead of the previously used permission `read`.

.. index:: ext:core, NotScanned
