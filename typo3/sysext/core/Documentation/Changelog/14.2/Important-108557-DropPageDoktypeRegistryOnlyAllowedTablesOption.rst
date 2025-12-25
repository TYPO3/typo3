..  include:: /Includes.rst.txt

..  _important-108557-1768612632:

======================================================================
Important: #108557 - Drop PageDoktypeRegistry onlyAllowedTables option
======================================================================

See :issue:`108557`

Description
===========

It is possible to limit allowed tables for Page Types ("doktype"). However, up
until now the default behavior when switching types was to ignore possible
violations to these rules. The behavior could be changed on a per doktype
level like this:

.. code-block:: php
    :caption: EXT:my_extension/ext_tables.php

    $dokTypeRegistry = GeneralUtility::makeInstance(PageDoktypeRegistry::class);
    $dokTypeRegistry->add(
        116,
        [
            'onlyAllowedTables' => true,
        ],
    );

This would make the Page Type `116` strict, when switching the Page Type
(aka doktype).

This option is now obsolete, as this functionality is always enabled.
Switching Page Types is no longer possible if it violates the configured
allowed tables, making the system more consistent.

Some remarks: This option was rarely used and often misunderstood. The option
to configure allowed tables was called `allowedTables`. It was not clear what
`onlyAllowedTables` should even mean without looking into the documentation.

On a rational point of view it does not make sense to configure restrictions
for Page Types, when they are ignored per default for the action of switching
types. Allowing to violate the rules makes them useless in the first place. So
either remove those restrictions altogether or make them always work like it
is done now.

..  index:: TCA, ext:core
