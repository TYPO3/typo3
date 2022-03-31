.. include:: /Includes.rst.txt

===================================
Deprecation: #88862 - T3_RETURN_URL
===================================

See :issue:`88862`

Description
===========

The JavaScript variable :js:`T3_RETURN_URL` holding the returnUrl sent with the current request either via `GET` or
`POST` has been marked as deprecated.


Impact
======

Since this is a global JavaScript variable, no proper deprecation layer applies and thus no deprecation notice is
rendered.


Affected Installations
======================

All third party extensions using :js:`T3_RETURN_URL` are affected.


Migration
=========

Get the submitted returnUrl by using PHP:

.. code-block:: php

   // Variant 1
   $returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

   // Variant 2
   $returnUrl = $request->getParsedBody()['returnUrl'] ?? $request->getQueryParams()['returnUrl'] ?? '';

.. index:: Backend, JavaScript, NotScanned, ext:backend
