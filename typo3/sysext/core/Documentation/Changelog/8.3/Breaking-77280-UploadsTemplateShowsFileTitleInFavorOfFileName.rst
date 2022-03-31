
.. include:: /Includes.rst.txt

==========================================================================
Breaking: #77280 - Uploads template shows file title in favor of file name
==========================================================================

See :issue:`77280`

Description
===========

The file title is now shown instead of the file name, if the title is present.


Impact
======

The output of the template changes.


Affected Installations
======================

Every installation using the "File links" content element with files having a specified title is affected.


Migration
=========

Override the template and remove the condition to restore the original behavior.

.. index:: Frontend
