.. include:: /Includes.rst.txt

.. _presets:

=======
Presets
=======

Any settings or preferences for the Import/Export tool will not be saved automatically.
If you want to reuse them, you need to save them as presets. Presets are internally stored in
the table :sql:`tx_impexp_presets`. Presets can be included in exports by
including this table in the export.

.. include:: /Usage/Presets.rst

To save a new preset go to :guilabel:`Export > File & Preset > Presets`, enter
a :guilabel:`Title of new preset` and select save.

To load a preset choose the desired preset from :guilabel:`Select preset` and
select load. To change a preset, make some changes to the settings and then select
:guilabel:`Save` below "Select presets".

.. warning::
    If you manually excluded records from the export, the :sql:`uid` values of these
    exports get stored in the preset on saving. This might lead to unexpected
    exclusions if the presets are used on another installation which desires to
    export records with the previously excluded :sql:`uid` values.
