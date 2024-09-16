.. include:: /Includes.rst.txt

.. _deprecation-104223-1721383576:

===============================================
Deprecation: #104223 - Fluid standalone methods
===============================================

See :issue:`104223`

Description
===========

Some methods in Fluid standalone v2 have been marked as deprecated:

*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper->registerUniversalTagAttributes()`
*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper->registerTagAttribute()`


Impact
======

Calling these methods is discouraged. They will log a deprecation level
error when used with Fluid standalone v4.


Affected installations
======================

Instances with extensions calling above methods.


Migration
=========

registerUniversalTagAttributes()
--------------------------------

Within tag based ViewHelpers, calls to :php:`registerUniversalTagAttributes()` should be removed.
This method has been marked as :php:`@deprecated` with Fluid standalone 2.12, will
log a deprecation level error with Fluid standalone v4, and will be removed with v5.

When removing the call, attributes registered by the call are now available in
:php:`$this->additionalArguments`, and no longer in :php:`$this->arguments`. This *may* need
adaption within single ViewHelpers, *if* they handle such attributes on their own. For example,
the common ViewHelper :html:`f:image` was affected within the TYPO3 Core. The following attributes
may need attention when removing :php:`registerUniversalTagAttributes()`: :html:`class`, :html:`dir`,
:html:`id`, :html:`lang`, :html:`style`, :html:`title`, :html:`accesskey`, :html:`tabindex`,
:html:`onclick`.

registerTagAttribute()
----------------------

Within tag based ViewHelpers, calls to :php:`registerTagAttribute()` should be removed.
This method has been marked as :php:`@deprecated` with Fluid standalone 2.12, will
log a deprecation level error with Fluid standalone v4, and will be removed with v5.

The call be often simply removed since arbitrary attributes not specifically registered
are just added as-is by :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper`.
This only needs attention
if single view helpers deal with such attributes within the :php:`render()` method:
When removing the call, those arguments are no longer available in :php:`$this->arguments`,
but in :php:`$this->additionalArguments`. Additional attention is needed with
attributes registered with type :php:`boolean`: Those usually have some handling
within :php:`render()`. To stay compatible, it can be helpful to not simply
remove the :php:`registerTagAttribute()` call, but to turn it into a call to
:php:`registerArgument()`.


.. index:: Fluid, PHP-API, FullyScanned, ext:fluid
