.. include:: /Includes.rst.txt

================================
Breaking: #81787 - Drop EXT:func
================================

See :issue:`81787`

Description
===========

The extension :php:`func` that added the "Web->Functions" backend module
has been dropped from core.

The extension is available in the TER and an install tool upgrade wizard
is in place to download and load the extension as compatibility layer for
other extensions that still rely on it by adding own sub modules to the module.

Extensions that need :php:`func` should already have a dependency in ext_emconf.php
similar to this:

.. code-block:: php

    'constraints' => [
        ...
        'depends' => [
            'func' => 9.0.0-9.0.99',
        ],
    ],

The version constraint depends on which core version the extension supports,
the func extension did not change much between core v8 and its extraction to TER
in v9.

.. index:: Backend, PHP-API, NotScanned, ext:func
