.. include:: /Includes.rst.txt

=========================================================
Important: #92100 - YAML imports follow declaration order
=========================================================

See :issue:`92100`

Description
===========

Since #78917 various places of TYPO3 can be configured using YAML. It's
also possible to use `imports` to split larger configurations into logical
subparts. The `imports` functionality previously imported the configured
files in the reverse order in which they were configured in the importing
file. Since it's sometimes important, e.g. when using `imports` in site
configurations, the import can now be configured to follow the declaration
order. The files are then imported in the exact same order as they are
configured in the importing file. Therefore, a new feature toggle
`yamlImportsFollowDeclarationOrder` is introduced. It defaults to
`false` for existing installations and to `true` for new installations.
This means, if you currently rely on the reverse order, nothing changes
in your existing installation.

Example:

.. code-block:: yaml

   imports:
     - { resource: "EXT:site/Configuration/SomeFile.yaml" }
     - { resource: "EXT:site/Configuration/AnotherFile.yaml" }

With `yamlImportsFollowDeclarationOrder` set to `true`:

1. :file:`EXT:site/Configuration/SomeFile.yaml`
2. :file:`EXT:site/Configuration/AnotherFile.yaml`

With `yamlImportsFollowDeclarationOrder` set to `false`:

1. :file:`EXT:site/Configuration/AnotherFile.yaml`
2. :file:`EXT:site/Configuration/SomeFile.yaml`

.. index:: Backend, ext:core
