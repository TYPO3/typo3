
.. include:: ../../Includes.txt

==========================================================================
Breaking: #30863 - Streamlined parameters for adding inline language files
==========================================================================

See :issue:`30863`

Description
===========

The method `addInlineLanguageLabelFile` of the `PageRenderer` handles the optional parameter `$stripFromSelectionName`, a string
that should be removed from any label key in the given file. This did not work until now, so the label keys were never stripped. As this
functionality is now working it could end up with different label keys in the output.


Impact
======

Inline Javascript label keys could have changed.


Affected Installations
======================

Any third party code using `PageRenderer->addInlineLanguageLabelFile()` with the parameter `$stripFromSelectionName` set to anything but
an empty string.


Migration
=========

Change the call to `PageRenderer->addInlineLanguageLabelFile()` with `$stripFromSelectionName = ''` or adjust your Javascript to handle
the now correctly rendered label keys.
