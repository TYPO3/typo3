
.. include:: ../../Includes.txt

==========================================================
Breaking: #72360 - Removed deprecated entry point fallback
==========================================================

See :issue:`72360`

Description
===========

The entry point fallback mechanism introduced with #68812 has been removed.

The following entry points won't work anymore.

.. code-block:: shell

	typo3/ajax.php
	typo3/alt_clickmenu.php
	typo3/alt_db_navframe.php
	typo3/alt_doc.php
	typo3/alt_file_navframe.php
	typo3/browser.php
	typo3/db_new.php
	typo3/dummy.php
	typo3/init.php
	typo3/login_frameset.php
	typo3/logout.php
	typo3/mod.php
	typo3/move_el.php
	typo3/show_item.php
	typo3/tce_db.php
	typo3/tce_file.php
	typo3/thumbs.php


Impact
======

All references / links to these entry points directly without using the proper API calls will result
in a 404 error.


Affected Installations
======================

Installations with third-party extensions that link directly to these files.


Migration
=========

Move all existing code in extensions that link to the deprecated entry points to use methods
like `BackendUtility::getModuleUrl()` and `BackendUtility::getAjaxUrl()` or the UriBuilder class.

.. index:: PHP-API, Backend
