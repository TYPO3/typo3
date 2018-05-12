.. include:: ../../Includes.txt

==========================================================================
Deprecation: #84980 - BackendUserAuthentication->addTScomment() deprecated
==========================================================================

See :issue:`84980`

Description
===========

Method :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->addTScomment()` has been deprecated.


Impact
======

The method has been used to add comments to `TSconfig` at runtime, those
comments however are never shown in the TYPO3 backend.


Affected Installations
======================

Instances with extensions calling :php:`BackendUserAuthentication->addTScomment()`
will throw a deprecation warning. It is however rather unlikely that extensions
rely on this widely unknown API method and methods calls were mostly core internal.
The extension scanner should find possible usages within extensions.


Migration
=========

Drop the method call.

.. index:: Backend, PHP-API, TSConfig, FullyScanned