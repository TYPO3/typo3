.. include:: /Includes.rst.txt

=======================================================
Breaking: #88427 - jsfunc.evalfield.js has been removed
=======================================================

See :issue:`88427`

Description
===========

The file :file:`jsfunc.evalfield.js`, responsible for form value evaluation and validation, has been removed. This job is
now done by :js:`TYPO3/CMS/Backend/FormEngineValidation` since TYPO3 7.4.


Impact
======

Extensions still relying on this file and its API will not work anymore.


Affected Installations
======================

All installations with third party extensions using this API are affected.


Migration
=========

In most cases no migration is necessary, unless this API is used in a custom built form. In such case, migrate to
FormEngine API to automatically use the new API.

.. index:: Backend, JavaScript, NotScanned, ext:backend
