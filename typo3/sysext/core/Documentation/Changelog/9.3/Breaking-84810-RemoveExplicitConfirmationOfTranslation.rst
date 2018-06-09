.. include:: ../../Includes.txt

===========================================================
Breaking: #84810 - Remove explicitConfirmationOfTranslation
===========================================================

See :issue:`84810`

Description
===========

Removes the explicitConfirmationOfTranslation feature which seems to be completely unused. Besides that it does no
longer fit in the new button bar introduced with the "unsplit the split button concept".
It has been detected before that there were missing icons for those buttons in earlier releases for which not even a bugreport was created.


Impact
======

The buttons will disappear if the setting was actually used.


Affected Installations
======================

Installations using the "Translation finished, save and close" and "Translation NOT finished, Save" buttons in their translation workflow.
Or possibly where the translation labels might be re-used in extensions.


Migration
=========

There's no migration needed as the setting is automatically removed. Possible calls to the previously public methods
will appear in the deprecation log but it seems to be highly unlikely those are used.

.. index:: Backend, LocalConfiguration, NotScanned, ext:core
