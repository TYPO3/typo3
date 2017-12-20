.. include:: ../../Includes.txt

==============================================
Deprecation: #82603 - Deprecate Storage module
==============================================

See :issue:`82603`

Description
===========

The RequireJS module :javascript:`TYPO3/CMS/Backend/Storage` has been marked as deprecated. The module has been split into the
modules :javascript:`TYPO3/CMS/Backend/Storage/Client` and :javascript:`TYPO3/CMS/Backend/Storage/Persistent`.

Impact
======

Using :javascript:`TYPO3/CMS/Backend/Storage` will trigger a warning in the browser's developer console.


Affected Installations
======================

All extensions using :javascript:`TYPO3/CMS/Backend/Storage` are affected.


Migration
=========

Instead of using :javascript:`Storage.Client` and :javascript:`Storage.Persistent` use the introduced modules instead.

Example code:

.. code-block:: javascript

	define(['TYPO3/CMS/Backend/Storage/Persistent'], function(PersistentStorage) {
		if (!PersistentStorage.isset('my-key')) {
			PersistentStorage.set('my-key', 'foobar');
		}
	});

.. index:: JavaScript, Backend, NotScanned
