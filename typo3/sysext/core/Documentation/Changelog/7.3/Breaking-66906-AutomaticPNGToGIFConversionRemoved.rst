
.. include:: ../../Includes.txt

==========================================================
Breaking: #66906 - Automatic PNG to GIF conversion removed
==========================================================

See :issue:`66906`

Description
===========

The configuration setting `$TYPO3_CONF_VARS[GFX][png_to_gif]` has been removed.


Impact
======

If the option is set in an installation, then PNG images used in the TYPO3 Frontend will now be kept as PNG, instead
of converting them to GIF files.


Affected Installations
======================

Installations having the option `$TYPO3_CONF_VARS[GFX][png_to_gif]` activated.
