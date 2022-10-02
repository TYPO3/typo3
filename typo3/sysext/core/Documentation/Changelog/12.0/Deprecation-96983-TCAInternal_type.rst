.. include:: /Includes.rst.txt

.. _deprecation-96983:

=======================================
Deprecation: #96983 - TCA internal_type
=======================================

See :issue:`96983`

Description
===========

The TCA option `internal_type` for type `group` has been removed altogether. The
only left usage for :php:`internal_type => 'folder'` is extracted into an own
TCA type `folder`.

Impact
======

The TCA option `internal_type` is not evaluated anymore. Using `internal_type`
with the option `folder` will be automatically migrated to
:php:`type => 'folder'`. A message will appear in the TCA migration module,
which lists all migrations done.

Affected Installations
======================

All installations which make use of the TCA option `internal_type`.

Migration
=========

The migration is fairly simple. Just change all occurrences of
:php:`internal_type => 'folder'` to :php:`type => 'folder'` and remove every
other occurrence of `internal_type`.

Before:

..  code-block:: php

    'columns' => [
        'aColumn' => [
            'config' => [
                'type' => 'group',
                'internal_type' => 'folder',
            ],
        ],
        'bColumn' => [
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
            ],
        ],
    ],

After:

..  code-block:: php

    'columns' => [
        'aColumn' => [
            'config' => [
                'type' => 'folder',
            ],
        ],
        'bColumn' => [
            'config' => [
                'type' => 'group',
            ],
        ],
    ],

.. index:: Backend, TCA, NotScanned, ext:backend
