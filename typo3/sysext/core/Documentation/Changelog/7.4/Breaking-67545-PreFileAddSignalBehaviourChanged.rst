
.. include:: ../../Includes.txt

======================================================
Breaking: #67545 - PreFileAdd signal behaviour changed
======================================================

See :issue:`67545`

Description
===========

In order to check whether an uploaded file exists already before uploading or to determine user preferences about present
files only when needed, the final name of the file is needed. As the final name can be altered by the `PreFileAdd` signal,
which originally always received the temporary uploaded file path as parameter, the signal will now receive an empty string
in `$sourceFilePath`.


Impact
======

All `PreFileAdd` slot methods that depend on the `$sourceFilePath` param must be adapted to handle the new empty string value.


Affected Installations
======================

All extensions that use the `PreFileAdd` signal and depend on the `$sourceFilePath` param.
