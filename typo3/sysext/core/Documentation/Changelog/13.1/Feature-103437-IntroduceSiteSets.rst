.. include:: /Includes.rst.txt

.. _feature-103437-1712062105:

======================================
Feature: #103437 - Introduce site sets
======================================

See :issue:`103437`

Description
===========

Site sets ship parts of site configuration as composable pieces. They are
intended to deliver settings, TypoScript, TSconfig and reference enabled content
blocks for the scope of a site.

Extensions can provide multiple sets in order to ship presets for different
sites or subsets (think of frameworks) where selected features are exposed
as a subset (example: `typo3/seo-xml-sitemap`).

A set is defined in an extension's subfolder in :file:`Configuration/Sets/`, for
example :file:`EXT:my_extension/Configuration/Sets/MySet/config.yaml`.

The folder name in :file:`Configuration/Sets/` is arbitrary, significant
is the `name` defined in :file:`config.yaml`. The `name` uses a `vendor/name`
scheme by convention, and *should* use the same vendor as the containing
extension. It may differ if needed for compatibility reasons (e.g. when sets are
moved to other extensions). If an extension provides exactly one set that should
have the same `name` as defined in :file:`composer.json`.

The :file:`config.yaml` for a set that is composed of three subsets looks as
follows:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/config.yaml

    name: my-vendor/my-set
    label: My Set

    # Load TypoScript, TSconfig and settings from dependencies
    dependencies:
      - some-namespace/slider
      - other-namespace/fancy-carousel


Sets are applied to sites via `dependencies` array in site configuration:

..  code-block:: yaml
    :caption: config/sites/my-site/config.yaml

    base: 'http://example.com/'
    rootPageId: 1
    dependencies:
      - my-vendor/my-set

Site sets can also be edited via the backend module
:guilabel:`Site Management > Sites`.

A list of available site sets can be retrieved with the console command
:bash:`bin/typo3 site:sets:list`.

Settings definitions
--------------------

Sets can define settings definitions which contain more metadata than just a
value: They contain UI-relevant options like `label`, `description`, `category`
and `tags` and types like `int`, `bool`, `string`, `stringlist`, `text` or
`color`. These definitions are placed in :file:`settings.definitions.yaml`
next to the site set file :file:`config.yaml`.

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/settings.definitions.yaml

    settings:
      foo.bar.baz:
        label: 'My example baz setting'
        description: 'Configure baz to be used in bar'
        type: int
        default: 5


Settings for subsets
--------------------

Settings for subsets (e.g. to configure settings in declared dependencies)
can be shipped via :file:`settings.yaml` when placed next to the set file
:file:`config.yaml`.

Note that default values for settings provided by the set do not need to be
defined here, as defaults are to be provided within
:file:`settings.definitions.yaml`.

Here is an example where the setting `styles.content.defaultHeaderType` — as
provided by `typo3/fluid-styled-content` — is configured via
:file:`settings.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/settings.yaml

    styles:
      content:
        defaultHeaderType: 1


This setting will be exposed as site setting whenever the set
`my-vendor/my-set` is applied to a site configuration.


Impact
======

Sites can be composed of sets where relevant configuration, templates, assets
and setting definitions are combined in a central place and applied to sites as
one logical volume.

Sets have dependency management and therefore allow sharing code between
multiple TYPO3 sites and extensions in a flexible way.


.. index:: Backend, Frontend, PHP-API, YAML, ext:core
