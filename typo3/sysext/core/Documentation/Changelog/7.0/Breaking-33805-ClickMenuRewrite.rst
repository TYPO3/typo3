
.. include:: ../../Includes.txt

====================================
Breaking: #33805 - ClickMenu Rewrite
====================================

See :issue:`33805`

Description
===========

The ClickMenu has seen some major changes under the hood. This implies some refactoring within JavaScript where existing
functionality is replaced by a AMD Module based on jQuery. The following JS methods are now replaced by respective
methods:

- showClickmenu_raw()
- Clickmenu.show()
- Clickmenu.populateData()

The new functionality is available via a global JavaScript object called TYPO3.ClickMenu which has equal
functions.

Additionally the ClickMenu is now used via AJAX completely, all non-AJAX calls are not supported anymore.

Impact
======

All third-party extensions using alt_clickmenu.php directly in the backend, or using the above JavaScript calls directly.

Affected installations
======================

Any installation using extensions having Backend modules using JavaScript functions for the ClickMenu inline
and installations using extensions using alt_clickmenu.php directly.

Migration
=========

Any use of "Clickmenu.show()" etc should be avoided and channelled through the according DocumentTemplate methods.

- BackendUtility::wrapClickMenuOnIcon()
- DocumentTemplate->getContextMenuCode()

If a backend module without a DocumentTemplate (with e.g. Extbase/Fluid) is used, this is done with a separate class
and related data attribute:

.. code-block:: html

	<a href="#" class="t3-js-clickmenutrigger" data-table="be_users" data-uid="{record.uid}" data-listframe="1">


.. index:: PHP-API, Backend
