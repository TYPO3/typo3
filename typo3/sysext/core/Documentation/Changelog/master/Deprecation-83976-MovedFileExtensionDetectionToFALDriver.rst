.. include:: ../../Includes.txt

==================================================================
Deprecation: #83976 - Moved file extension detection to FAL driver
==================================================================

See :issue:`83976`

Description
===========

The only object that is allowed to handle the physical file in the FAL is the driver. As that's the
only instance that knows how to access the file.

The definition of the FAL driver method `getFileInfoByIdentifier()` is enhanced with the return
value `extension`.


Impact
======

Installations with a FAL driver `getFileInfoByIdentifier()` method that doesn't return the
`extension` value will see deprecation messages in the log.


Affected Installations
======================

Installations with 3rd party FAL drivers.


Migration
=========

Adjust the `getFileInfoByIdentifier()` method of your file drivers to return the `extension` value.

.. index:: FAL, NotScanned