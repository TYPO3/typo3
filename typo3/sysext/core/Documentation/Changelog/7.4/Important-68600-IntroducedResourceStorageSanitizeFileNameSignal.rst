
.. include:: ../../Includes.txt

======================================================================
Important: #68600 - Introduced ResourceStorage SanitizeFileName signal
======================================================================

See :issue:`68600`

Description
===========

In order to check whether an uploaded/newly added file already exists before uploading it or to ask for
user preferences about already existing files only when needed, the final name for the uploaded file is needed.

Before #68600 the PreFileAdd signal was documented to have the ability to change the `$targetFileName`,
but the signal expects the local file path of the file. Since this information isn't available when checking
only by a file name if a file already exists, a new signal has been added to `TYPO3\CMS\Core\Resource\ResourceStorage`,
which is emitted when the ResourceStorage is asked to sanitize a file name.


Affected Installations
======================

All installations with extensions that use the PreFileAdd signal to change/sanitize a file name.
This logic should be moved to the new sanitizeFileName signal.
