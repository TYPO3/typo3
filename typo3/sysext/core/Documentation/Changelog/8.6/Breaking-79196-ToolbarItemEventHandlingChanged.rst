.. include:: ../../Includes.txt

======================================================
Breaking: #79196 - Toolbar item event handling changed
======================================================

See :issue:`79196`

Description
===========

With the introduction of the topbar reloading mechanism, the event handling of toolbar items has changed. Reason is
that the event information gets lost, as the whole topbar is rendered from scratch after a reload.


Impact
======

After reloading the topbar, non-migrated events will not get triggered anymore.


Affected Installations
======================

All installations with old-fashioned toolbar item registrations.


Migration
=========

In most cases it's sufficient to replace the register function with `Viewport.Topbar.Toolbar.registerEvent()`.

Example:

.. code-block:: javascript

	define(['jquery', 'TYPO3/CMS/Backend/Viewport'], function($, Viewport) {
		// old registration
		$(MyAwesomeItem.doStuff)

		// new registration
		Viewport.Topbar.Toolbar.registerEvent(MyAwesomeItem.doStuff);
	});

.. index:: Backend, JavaScript