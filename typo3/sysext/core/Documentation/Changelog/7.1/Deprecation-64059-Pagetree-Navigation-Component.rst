
.. include:: ../../Includes.txt

==========================================================
Deprecation: #64059 - Non-ExtJS Page Tree Navigation Frame
==========================================================

See :issue:`64059`

Description
===========

The non-ExtJS page tree navigation frame which was used in the core until TYPO3 CMS 4.5, is still available and can be
included within a module, if the module is registering a navFrameScript in ext_tables.php:

.. code-block:: php

	'navFrameScript' => 'alt_db_navframe.php'


Impact
======

Usage of the PHP class, and the entry script typo3/alt_db_navframe.php has been marked as deprecated.


Affected installations
======================

All installations with extensions using modules with the non-ExtJS page tree navigation frame.


Migration
=========

Use the ExtJS navigationComponentID instead within the module registration.


.. index:: PHP-API, JavaScript, Backend
