
.. include:: /Includes.rst.txt

=================================================================
Breaking: #65727 - Don't provide access to localPath of FAL files
=================================================================

See :issue:`65727`

Description
===========

It was possible to retrieve the local path of a FAL file via TypoScript

.. code-block:: typoscript

   a = TEXT
   a.value.data = file:current:localPath

The localPath property has been dropped for the following reasons:

* The implementation used allow write access to the file and hence created a local copy which created useless file garbage.

* Changing this to read-only access would cause the LocalDriver to return the true local path to the file, which would
  open the possibility to file manipulation via "side channel" of FAL. This would make the FAL data inconsistent.


Impact
======

Any TypoScript using this file-property will stop working.


Affected Installations
======================

Any installation with TypoScript using this file-property


Migration
=========

There is no other possibility to retrieve this information. Use the FAL API.


.. index:: FAL, TypoScript, Frontend
