..  include:: /Includes.rst.txt

..  _breaking-107831-1761307146:

===================================================================================================
Breaking: #107831 - AfterCachedPageIsPersistedEvent no longer receives TypoScriptFrontendController
===================================================================================================

See :issue:`107831`

Description
===========

The frontend rendering related event :php:`AfterCachedPageIsPersistedEvent`
had to be changed due to the removal of class :php:`TypoScriptFrontendController`:
Method :php:`getController()` is removed.


Impact
======

Event listeners that call :php:`getController()` will trigger a fatal PHP error and
have to be adapted.


Affected installations
======================

Instances with extensions listening for event :php:`AfterCachedPageIsPersistedEvent`
may be affected. The extension scanner will find affected extensions.

Migration
=========

In most cases, the data that was previously provided by :php:`TypoScriptFrontendController`
can now be found in the :php:`Request` whith is available using :php:`$event->getRequest()`.

See :ref:`breaking-102621-1701937690` for more information.


..  index:: Frontend, NotScanned, ext:frontend
