
.. include:: /Includes.rst.txt

=========================================================
Breaking: #63818 - Removed Static file edit functionality
=========================================================

See :issue:`63818`

Description
===========

A special TCA configuration enabled RTE fields to write its content to sections within files. The configuration
was done in `defaultExtras` array, `static_write` section and documented in
TCA reference->AdditionalFeatures->SpecialConfigurationOptions.

This functionality has been removed without substitution.

Impact
======

Content of RTE fields can no longer be written to files.


Affected installations
======================

In the unlikely case that this feature was used by anyone its usage can be located by searching for `static_write`
keyword in TCA configuration.

Migration
=========

Move this logic elsewhere, eg. use hooks in DataHandler to write out DB content.


.. index:: TCA, RTE, Backend
