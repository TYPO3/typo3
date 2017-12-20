
.. include:: ../../Includes.txt

=================================================
Breaking: #51099 - Streamline settings/conditions
=================================================

See :issue:`51099`

Description
===========

The default TypoScript for the `pi2` (extbase) plugin of EXT:indexed_search sets a TypoScript
variable `plugin.tx_indexedsearch.settings.displayRules = 1`, while the default fluid template
uses the TypoScript setting `plugin.tx_indexedsearch.settings.showRules`. This change makes the
default fluid template for the extbase plugin of EXT:indexed_search use the correct TypoScript
setting.


Impact
======

Instances of the extbase plugin (`pi2`) of EXT:indexed_search using the default fluid template and
explicitly configured to *hide* the search rules deliberately using the wrong TypoScript setting
`plugin.tx_indexedsearch.settings.showRules = 0` *will show* the search rules after this update.


Affected Installations
======================

Installations using the extbase plugin (`pi2`) of EXT:indexed_search with default template and relying
on the undocumented TypoScript setting `plugin.tx_indexedsearch.settings.showRules = 0`.


Migration
=========

Change all occurrences of the TypoScript setting `plugin.tx_indexedsearch.settings.showRules = 0`
to `plugin.tx_indexedsearch.settings.displayRules = 1`.


.. index:: TypoScript, ext:indexed_search
