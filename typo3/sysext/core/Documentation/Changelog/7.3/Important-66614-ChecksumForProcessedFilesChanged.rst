
.. include:: ../../Includes.txt

===================================================================
Important: #66614 - Checksums for processed files have been changed
===================================================================

See :issue:`66614`

Description
===========

The base data used for the checksum calculation of processed files has been changed.
The checksum is used to identify changes which require regeneration of processed files.

Formerly the `GFX` section of the `TYPO3_CONF_VARS` was included in this base data,
which caused weird problems in some cases.

With TYPO3 CMS 7.3 (and 6.2.13) this has been changed. In case you are adjusting `GFX` settings and you want
processed files to be regenerated, you need to manually clean the existing processed files by using the Clean up
utility in the Install Tool.

Since the base data is different now, the Core would not recognize the existing processed files as valid files and would
delete those and build a new version.
In case you are having a large installation, you might want to avoid this costly operation.
The Install Tool provides a dedicated Upgrade Wizard for you, which avoids the expensive regeneration of processed files
by updating the checksum of all existing processed files.

.. note::

	The Upgrade Wizard is only relevant for you if you're upgrading from any TYPO3 CMS version below 7.3 or 6.2.13.
	Any upgrade from 7.3 or later or from 6.2.13 or later to a newer version does **not** require to run the wizard.

