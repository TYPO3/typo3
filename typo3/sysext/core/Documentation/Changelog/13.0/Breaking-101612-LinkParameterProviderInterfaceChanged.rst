.. include:: /Includes.rst.txt

.. _breaking-101612-1691447955:

==========================================================
Breaking: #101612 - LinkParameterProviderInterface changed
==========================================================

See :issue:`101612`

Description
===========

The PHP interface :php:`\TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface`
has changed. The interface is used to generate URLs with query parameters for links
within element browsers or link browsers in the TYPO3 backend.

The methods :php:`getScriptUrl()` and :php:`isCurrentlySelectedItem()` have been removed
from the interface, as the implementing link browsers do not need this information anymore
due to simplification in routing.

The method :php:`getUrlParameters()` now has a native return type :php:`array`, whereas
previously this was only type-hinted.


Impact
======

When accessing implementing PHP objects, it should be noted that these methods do not
exist anymore. When called this might result in fatal PHP errors.

When implementing the PHP interface, the implementing code will fail due to missing return
types.


Affected installations
======================

TYPO3 installations with custom implementations of this interface.


Migration
=========

For extensions implementing the interface, the return type for  :php:`getUrlParameters()`
can be added in order to be TYPO3 v12+ compatible. For v13-only compatibility,
it is recommended to remove the superfluous methods.

.. index:: PHP-API, PartiallyScanned, ext:backend
