.. include:: /Includes.rst.txt

===============================================
Breaking: #88411 - TBE_EDITOR.typo3form removed
===============================================

See :issue:`88411`

Description
===========

The global object :js:`TBE_EDITOR.typo3form` and its backward layers :js:`typo3FormFieldSet` and :js:`typo3FormFieldGet`
have been removed.


Impact
======

Any extension relying on this code will not work anymore.


Affected Installations
======================

All installations using the removed code are affected.


Migration
=========

No direct migration possible. Refer to the FormEngine JavaScript API.

.. index:: Backend, JavaScript, NotScanned, ext:backend
