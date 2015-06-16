======================================================
Breaking: #67545 - PreFileAdd signal behaviour changed
======================================================

Description
===========

In order to check whether an uploaded file exists already before uploading it or
to ask about user preferences about already existent files only when needed,
the final name of the file was need. As the final name can be altered by PreFileAdd signal,
which originally always received the temporary uploaded file path as parameter. But as this isn't
available in the file name check $sourceFilePath will be an empty string then.


Impact
======

All PreFileAdd slot methods that depend on the $sourceFilePath param must be adapted to handle the new empty string value.


Affected Installations
======================

All extensions that use the PreFileAdd signal and depend on the $sourceFilePath param.