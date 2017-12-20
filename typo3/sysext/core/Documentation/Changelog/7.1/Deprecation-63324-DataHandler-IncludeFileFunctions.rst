
.. include:: ../../Includes.txt

===================================================================
Deprecation: #62864 - DataHandler->include_filefunctions deprecated
===================================================================

See :issue:`62864`

Description
===========

The public property `->include_filefunctions` has been marked as deprecated.
It has not been formally defined and was only created dynamically in the code.

Impact
======

In the history of the core this property has never been used (read). Extensions might have used it.


Affected installations
======================

All installations running extensions that rely on reading this property. Currently no affected extensions are known.

Migration
=========

If your extension needs to know whether the BasicFileUtility has been instantiated in DataHandler it
could use `$datahandler->fileFunc instanceof \TYPO3\CMS\Core\Utility\File\BasicFileUtility`


.. index:: PHP-API, Backend
