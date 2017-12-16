
.. include:: ../../Includes.txt

================================================
Breaking: #61510 - Improvement of indexed_search
================================================

See :issue:`61510`

Description
===========

The extension indexed_search is improved in the backend and frontend.

Backend
-------

Previously the functionality of indexed_search has been scattered to multiple modules.
Information about indexed_search was available in a custom module in "Admin tools" and 2 sections in the "Info" module.

The complete code has been moved to a central place, which is now a custom module in the area "Web" and has been rewritten
by using Extbase & Fluid. Translations and a modern UI have been added as well.


Impact
======

Changes in the Backend
----------------------

The previous user configuration for indexed_search modules is not working anymore.
Therefore editors won't see the module anymore after login.

Changes in the Frontend
-----------------------

The TypoScript configuration changed. If indexed_search is installed, it is automatically activated:

.. code-block:: typoscript

	config.index_enable = 1
	config.index_externals = 1

Affected installations
======================

All installations using indexed_search

Migration
=========

Backend
-------

Reconfigure the backend users and groups if users need to see the module of indexed_search.
