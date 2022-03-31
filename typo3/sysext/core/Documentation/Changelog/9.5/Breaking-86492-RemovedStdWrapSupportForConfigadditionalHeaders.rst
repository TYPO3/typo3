.. include:: /Includes.rst.txt

=======================================================================
Breaking: #86492 - Removed stdWrap support for config.additionalHeaders
=======================================================================

See :issue:`86492`

Description
===========

The feature to use :typoscript:`stdWrap` for :typoscript:`config.additionalHeaders` has been removed due to
an incompatibility with page caching.


Impact
======

:typoscript:`stdWrap` cannot be used anymore to manipulate additional headers set via TypoScript.


Affected Installations
======================

Any TYPO3 instance that uses this feature, which has been introduced with version 9.0.


Migration
=========

For the time being there will be no TypoScript based solution for dynamic HTTP headers.

Consider implementing a PSR-15 middleware to add advanced logic for additional frontend response headers.

.. index:: Frontend, TypoScript, NotScanned, ext:frontend
