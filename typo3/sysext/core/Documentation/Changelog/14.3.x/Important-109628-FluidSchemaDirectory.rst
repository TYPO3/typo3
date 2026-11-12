..  include:: /Includes.rst.txt

..  _important-109628-1784101504:

==========================================================================
Important: #109628 - fluid:schema:generate writes to a dedicated directory
==========================================================================

See :issue:`109628`

Description
===========

The :bash:`fluid:schema:generate` command writes the generated Fluid XSD
files to the dedicated directory :file:`var/transient/fluid-schema/` instead
of :file:`var/transient/`. Schema files of previous runs in
:file:`var/transient/` are removed when the command is executed.

IDE configurations or scripts that reference the schema files in
:file:`var/transient/` need to be adapted to the new location.

Additionally, the command accepts an optional :bash:`path` argument. It can
be absolute or relative to the project root and defines the directory the
schema files are written to:

..  code-block:: bash

    vendor/bin/typo3 fluid:schema:generate .tool-support/fluidschema/

This allows writing the schema files to a directory that is picked up by the
IDE, for example when :file:`var/` is excluded from indexing.

The target directory should be used for the generated schema files only: all
existing :file:`schema_*.xsd` files in it are removed before the new files
are written.

The command exits with an error if the directory can not be created or a
schema file can not be written.

..  index:: CLI, Fluid, ext:fluid
