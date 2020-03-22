
.. include:: ../../Includes.txt

================================================================================
Breaking: #77156 - TSconfig and TStemplate soft references functionality removed
================================================================================

See :issue:`77156`

Description
===========

Soft references based on TSconfig, TStemplate and images within :file:`fileadmin/` have been removed.

The soft reference keys `TSconfig` and `TStemplate` that could previously be set via
:php:`$GLOBALS[TCA][$table][columns][$column][config][softref]` are not evaluated anymore.

The soft reference keys `images`, `typolink` and `typolink_tag` are not evaluating files within :file:`fileadmin/`
anymore that are not based on the File Abstraction Layer.

The public PHP property :php:`SoftReferenceIndex::$fileAdminDir` has been removed.

The following PHP methods has been removed without substitution:

- :php:`SoftReferenceIndex::findRef_TStemplate()`
- :php:`SoftReferenceIndex::findRef_TSconfig()`
- :php:`SoftReferenceIndex::fileadminReferences()`


Impact
======

Setting the softref properties `TSconfig` and `TStemplate` within TCA will not be evaluated anymore and will
throw a deprecation message.

Calling any of the PHP methods above will throw a fatal PHP error.

The soft reference index will not be updated with the TSconfig and TStemplate properties anymore, as well
as files directly linked or referenced with the :file:`fileadmin/` directory.


Affected Installations
======================

If the soft reference index is evaluated in a third-party extension, this might result in unexpected behaviour.

All TYPO3 instances using extensions setting TSconfig or TStemplate soft references in TCA are also affected.


Migration
=========

Remove the softref keys `TStemplate` and `TSconfig` from the TCA definition of the third party extensions.

.. index:: TCA, PHP-API
