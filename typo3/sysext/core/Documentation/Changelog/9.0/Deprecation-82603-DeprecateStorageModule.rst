.. include:: /Includes.rst.txt

==============================================
Deprecation: #82603 - Deprecate Storage module
==============================================

See :issue:`82603`

Description
===========

The RequireJS module :js:`TYPO3/CMS/Backend/Storage` has been marked as deprecated. The module has been split into the
modules :js:`TYPO3/CMS/Backend/Storage/Client` and :js:`TYPO3/CMS/Backend/Storage/Persistent`.

Impact
======

Using :js:`TYPO3/CMS/Backend/Storage` will trigger a warning in the browser's developer console.


Affected Installations
======================

All extensions using :js:`TYPO3/CMS/Backend/Storage` are affected.


Migration
=========

Instead of using :js:`Storage.Client` and :js:`Storage.Persistent` use the introduced modules instead.

Example code:

.. code-block:: javascript

	define(['TYPO3/CMS/Backend/Storage/Persistent'], function(PersistentStorage) {
		if (!PersistentStorage.isset('my-key')) {
			PersistentStorage.set('my-key', 'foobar');
		}
	});

.. index:: JavaScript, Backend, NotScanned
