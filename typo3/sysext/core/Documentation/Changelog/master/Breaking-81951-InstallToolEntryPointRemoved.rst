.. include:: ../../Includes.txt

===================================================
Breaking: #82433 - Install Tool entry point removed
===================================================

See :issue:`82433`

Description
===========

The canonical entry point for accessing the install tool now is `typo3/install.php`. The previous entrypoint
located in `typo3/sysext/install/Start/Install.php` has been removed. The deprecated entrypoint located under
`typo3/install/index.php` still exists, but `typo3/install.php` is the new way to access the install tool,
available since TYPO3 8 LTS.


Impact
======

Accessing `typo3/sysext/install/Start/Install.php` will trigger a Server-based "Page Not Found" error message.


Affected Installations
======================

Every TYPO3 installation using the old entry point is affected.


Migration
=========

Change bookmarks or scripts from the old entry point to the new one.

.. index:: Backend, NotScanned
