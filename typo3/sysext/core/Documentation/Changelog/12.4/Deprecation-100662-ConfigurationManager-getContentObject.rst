.. include:: /Includes.rst.txt

.. _deprecation-100662-1681906563:

===============================================================
Deprecation: #100662 - ConfigurationManager->getContentObject()
===============================================================

See :issue:`100662`

Description
===========

The Extbase-related method
:php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface->getContentObject()`
has been marked as deprecated in TYPO3 v12 and should not be used anymore.

Impact
======

Calling :php:`ConfigurationManagerInterface->getContentObject()` will trigger
a deprecation level log message in TYPO3 v12, the method will be removed
from the interface together with their implementations with TYPO3 v13.


Affected installations
======================

Instances with Extbase extensions that use :php:`getContentObject()` on
injected :php:`ConfigurationManager` instances are affected. The extension
scanner has not been configured to find these calls, since the method
name is used in different scope as well and would trigger too many
false positives.


Migration
=========

There may be instances with Extbase controllers that need to retrieve
data from the current content object that initiated the frontend Extbase
plugin call.

In this case, controllers can access the current content object from the
Extbase request object using :php:`$request->getAttribute('currentContentObject')`
instead.


.. index:: PHP-API, NotScanned, ext:extbase
