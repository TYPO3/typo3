:navigation-title: Presets

..  include:: /Includes.rst.txt
..  _presets:

=====================================================
Saving and reusing export configurations with presets
=====================================================

..  include:: /Images/AutomaticScreenshots/Presets.rst.txt

Any configuration settings made in the :ref:`export module <export>` are not
saved automatically. To reuse export configurations, you need to save them
as presets.

Presets are stored internally in the :sql:`tx_impexp_presets` table and can
be included in export files by adding this table to the export.

..  contents:: Table of content

..  warning::

    If you have manually excluded records from export, the :sql:`uid` values of
    those exclusions are saved in the preset. This can lead to unexpected
    exclusions if you reuse the preset in another TYPO3 instance.

    Therefore, review the excluded records in the "Configuration" tab
    thoroughly when you load a preset.

..  _presets-save:

Saving a new preset
===================

To save a new preset:

1. Go to :guilabel:`Export > File & Preset > Presets`.
2. Enter a :guilabel:`Title of new preset` (A.1).
3. Click :guilabel:`Save` (A.2).

..  _presets-load:

Loading an existing preset
==========================

To load a saved preset:

1. Select the desired preset from :guilabel:`Select Preset` (B.1).
2. Click :guilabel:`Load` (B.2).

..  _presets-modify:

Modifying an existing preset
============================

To modify an existing preset:

1. Load the preset as described above.
2. Make the required changes to the export settings.
3. Select the same preset again in :guilabel:`Select Preset` (!).
4. Click :guilabel:`Save` to overwrite the preset.

..  _presets-visibility:

Managing preset visibility
==========================

Checking :guilabel:`Public` allows any TYPO3 backend user to load this preset.
If left unchecked, only the creator can access the preset.
