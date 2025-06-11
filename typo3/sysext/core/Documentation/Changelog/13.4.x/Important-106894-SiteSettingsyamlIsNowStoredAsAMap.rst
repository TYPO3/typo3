..  include:: /Includes.rst.txt

..  _important-106894-1750144877:

==============================================================
Important: #106894 - Site settings.yaml is now stored as a map
==============================================================

See :issue:`106894`

Description
===========

Site settings are defined as a map of keys, with a defined
type and default.

The values were previously stored as a tree representation in
:file:`settings.yaml`, e.g.:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/settings.yaml

    foo:
      bar: 'value'

This tree representation is easier to write, but has the
drawback that arbitrary keys like `foo.bar` and `foo.bar.baz`
exclude each other, as the subkey `baz` would be represented
as a value of `foo.bar` in tree representation.

Note that TypoScript constants can express subkey constants since
TypoScript can store a value and childnodes for every node, which
means that existing extensions – that migrate to site sets – require
this mixture of setting identifiers to be supported in order to
avoid breaking existing settings.

The storage format of settings.yaml is now changed to use
a map (like settings.definitions.yaml already do) to store
setting values, in order to overcome the mentioned limitation.
It is still supported to *read* from a tree, but the settings editor
will convert the tree to a map when persisting values.

Given the following setting definition:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/settings.definitions.yaml

    settings:
      foo.bar:
        type: string
        default: ''
        label: FooBar
      foo.bar.baz:
        type: string
        default: ''
        label: FooBarBaz


A map will be stored in :file:`settings.yaml` that is able to store values for
both setting identifiers:

..  code-block:: yaml
    :caption: typo3conf/sites/mysite/settings.yaml

    foo.bar: 'Foo bar value'
    foo.bar.baz: 'Foo Bar baz value'

Also site sets are advised to use this format for settings provided in their sets
:file:`settings.yaml` file.

Existing anonymous settings (pre v13 style, e.g. settings without a
matching settings.definitions.yaml definition) will be preserved as
a tree, since it is not known which tree node is key or a value.

..  index:: YAML, ext:core
