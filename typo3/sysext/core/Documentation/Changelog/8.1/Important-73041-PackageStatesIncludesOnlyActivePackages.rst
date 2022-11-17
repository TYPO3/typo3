
.. include:: /Includes.rst.txt

.. _important-73041:

===============================================================
Important: #73041 - PackageStates Includes Only Active Packages
===============================================================

See :issue:`73041`

Description
===========

The information about available packages in the system located in :file:`typo3conf/PackageStates.php` was
thinned out to only include the extension keys of the active (= installed) extensions.

.. index:: PHP-API
