
.. include:: ../../Includes.txt

============================================================================
Breaking: #77342 - Removed templateFile override via FlexForm in EXT:felogin
============================================================================

See :issue:`77342`

Description
===========

The possibility to override the template file via FlexForm when inserting a Frontend Login plugin has been removed.

The need for the upload folder `uploads/tx_felogin` has been removed, and the folder is not generated anymore.


Impact
======

The template file cannot be selected anymore from the Frontend Login plugin. Existing installations using this option before
will fall back to the TypoScript setting silently.


Affected Installations
======================

TYPO3 instances using the `templateFile` option via FlexForms in Frontend Login plugins.


Migration
=========

Use the TypoScript setting `plugin.tx_felogin.templateFile` to set an alternative template file.

.. index:: Frontend, ext:felogin, TypoScript
