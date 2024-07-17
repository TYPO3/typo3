.. include:: /Includes.rst.txt

.. _deprecation-104223-1721383576:

===============================================
Deprecation: #104223 - Fluid standalone methods
===============================================

See :issue:`104223`

Description
===========

Some methods in Fluid standalone v2 have been marked as deprecated:

* :php:`registerUniversalTagAttributes()`


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
This method has been marked as :php:`@deprecated` with Fluid standalone 2.12, and
will log a deprecation level error with Fluid standalone v4. When removing the call,
attributes registered by the call are now available in :php:`$this->additionalArguments`,
and no longer in :php:`$this->arguments`. This *may* need adaption within single ViewHelpers,
*if* they handle such attributes on their own. For example, the common ViewHelper :html:`f:image`
was affected within the TYPO3 core. The following attributes may need attention when removing
:php:`registerUniversalTagAttributes()`: :html:`class`, :html:`dir`, :html:`id`,
:html:`lang`, :html:`style`, :html:`title`, :html:`accesskey`, :html:`tabindex`,
:html:`onclick`.


.. index:: Fluid, PHP-API, FullyScanned, ext:fluid
