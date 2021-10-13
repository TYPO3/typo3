.. include:: /Includes.rst.txt

.. _presets:

=======
Presets
=======

.. warning::
   If you have manually excluded records from export, the :sql:`uid` values of
   those exports are saved in the preset. This can lead to unexpected exclusions
   if you want to use the preset in another TYPO3 instance. Therefore, check
   the excluded records in the tab "Configuration" thoroughly when you have
   loaded a preset.

Any configuration settings of the :ref:`export module<export>` are not
automatically saved. If you want to reuse them, you must save them as presets.
Presets are stored internally in the :sql:`tx_impexp_presets` table and can be
included in exports by including this table in the export.

.. include:: /Images/AutomaticScreenshots/Presets.rst.txt

To save a new preset, go to :guilabel:`Export > File & Preset > Presets`, enter
a :guilabel:`Title of new preset` (A.1) and select :guilabel:`Save` (A.2).

To load a preset, choose the desired preset from :guilabel:`Select Preset` (B.1)
and select :guilabel:`Load` (B.2).

To modify a preset, load it, make changes to the settings, select the preset
in :guilabel:`Select Preset` again (!) and save it.

Checking :guilabel:`Public` means that any TYPO3 backend user can load this
preset, while otherwise this is restricted to the creator of the preset.
