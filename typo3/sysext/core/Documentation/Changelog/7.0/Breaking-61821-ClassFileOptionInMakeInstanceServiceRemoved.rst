
.. include:: ../../Includes.txt

==================================================================
Breaking: #61821 - classFile option in makeInstanceService removed
==================================================================

See :issue:`61821`

Description
===========

The option "classFile" in :code:`\TYPO3\CMS\Core\Utility\GeneralUtility\makeInstanceService()` is removed.
This should now be done by the respective ext_autoload.php of each extension.


Impact
======

Extension classes relying on the "classFile" registration for autoloading will not be loaded anymore.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed class loading registration method.


Migration
=========

Use the ext_autoload.php file to autoload the class.


.. index:: PHP-API
