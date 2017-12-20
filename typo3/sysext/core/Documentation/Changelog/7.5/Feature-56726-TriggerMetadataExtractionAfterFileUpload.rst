
.. include:: ../../Includes.txt

===============================================================
Feature: #56726 - Trigger metadata extraction after file upload
===============================================================

See :issue:`56726`

Description
===========

Before #56726 the metadata extraction was only called through the extract metadata
scheduler task.
So when a editor uploaded a new file he had to wait until the scheduler task had
been triggered again and extracted the metadata.

Now the metadata extraction is by default triggered after adding/uploading a file
in the BE or when the FAL API is used `ResourceStorage::addFile()`,
`ResourceStorage::replaceFile()` and `ResourceStorage::addUploadedFile()`.

In some special situations it isn't desired to have metadata extraction direct
after file upload/adding a file to the storage.
For these cases the automatic extraction can be disabled in File Storage configuration.


Impact
======

The flag is by default set for all existing and a new storage. When you have some
special use-case where automatic extraction of metadata is not desired the flag
can be disabled in File Storage configuration.


.. index:: FAL, PHP-API, Backend
