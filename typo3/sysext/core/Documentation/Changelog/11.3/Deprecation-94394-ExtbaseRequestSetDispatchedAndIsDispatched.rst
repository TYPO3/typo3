.. include:: /Includes.rst.txt

========================================================================
Deprecation: #94394 - Extbase Request setDispatched() and isDispatched()
========================================================================

See :issue:`94394`

Description
===========

To further prepare towards PSR-7 requests in Extbase, the two
methods :php:`TYPO3\CMS\Extbase\Mvc\Request->setDispatched()` and
:php:`TYPO3\CMS\Extbase\Mvc\Request->isDispatched()` have been
marked as deprecated.


Impact
======

Using the methods is discouraged. The Extbase dispatcher still
recognizes them and acts accordingly, the methods do **not** raise
a deprecation level log entry, though.


Affected Installations
======================

Some Extbase based extensions may use :php:`setDispatched()`, but
it's rather unlikely since that flag has been mostly used internally
through existing helper methods in Extbase controllers.

The extension scanner will find possible candidates.


Migration
=========

Action dispatching in Extbase now depends on the returned response:

*  A casual 2xx Response from a controller action that for instance contains HTML
   or Json stops Extbase dispatching, the response is later returned to the client.

*  An Extbase :php:`ForwardResponse` instructs the dispatcher to dispatch
   internally to another controller action.

*  A 3xx :php:`RedirectResponse` stops dispatching and is returned to the client
   to initiate some client redirect.

.. index:: PHP-API, FullyScanned, ext:extbase
