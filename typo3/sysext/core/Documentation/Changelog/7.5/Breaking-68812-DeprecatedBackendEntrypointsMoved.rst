
.. include:: /Includes.rst.txt

=====================================================================
Breaking: #68812 - Old Backend Entrypoints moved to deprecation layer
=====================================================================

See :issue:`68812`

Description
===========

The backend entry points within the typo3/ directory which have been marked as deprecated in favor of using typo3/index.php
directly as Entry Point via Request Handling, have been moved to a deprecation.php file.

The following files have therefore been removed from the typo3/ directory directly:

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


The typo3/install/ entrypoint is now also redirected with a rewrite rule.

Impact
======

All references / links to these entry points directly without using the proper API calls will result
in a 404 error.

If an Apache webserver is used with the enabled mod_rewrite module, a .htaccess file placed inside typo3/ will
rewrite the URLs to the deprecated.php and throw a deprecation warning.

For Nginx and IIS an alternative for the rewrite rules in the shipped typo3/.htaccess within needs to be added.


Affected Installations
======================

Installations with third-party extensions that link directly to these files.


Migration
=========

Move all existing code in extensions that link to the deprecated entry points to use methods
like `BackendUtility::getModuleUrl()` and `BackendUtility::getAjaxUrl()` or the UriBuilder class.


.. index:: PHP-API
