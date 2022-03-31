.. include:: /Includes.rst.txt

===========================================================
Breaking: #91578 - IRRE related JavaScript has been removed
===========================================================

See :issue:`91578`

Description
===========

The JavaScript functions :js:`TBE_EDITOR.fieldChanged_fName` and its legacy
alias :js:`TBE_EDITOR_fieldChanged_fName`, used to extract the table name, field
name and uid from the incoming field name, have been removed.


Impact
======

Calling any of the removed function will trigger a JavaScript error.


Affected Installations
======================

All 3rd party extensions calling these functions are affected.


Migration
=========

No migration is possible as this is IRRE-related code only.

.. index:: Backend, JavaScript, NotScanned, ext:backend
