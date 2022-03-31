.. include:: /Includes.rst.txt

==========================================================================================================
Deprecation: #84171 - Adding GeneralUtility::getUrl RequestHeaders as non-associative array are deprecated
==========================================================================================================

See :issue:`84171`

Description
===========

RequestHeaders passed to `getUrl()` as string (format `Header:Value`) have been marked as deprecated.
Associative arrays should be used instead.


Impact
======

Using `GeneralUtility::getUrl()` request headers in a non-associative way will trigger an `E_USER_DEPRECATED` PHP error.


Affected Installations
======================

All using request headers for `GeneralUtility::getUrl()` in a non-associative way.


Migration
=========

Use associative arrays, for example:

.. code-block:: php

   $headers = ['Content-Language: de-DE'];

will become

.. code-block:: php

   $headers = ['Content-Language' => 'de-DE'];

.. index:: PHP-API, NotScanned
