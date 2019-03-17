.. include:: ../../Includes.txt

=========================================
Feature: #57082 - New TCA type "language"
=========================================

See :issue:`57082`

Description
===========

A new TCA field type called `language` has been added to TYPO3 Core. It's main
purpose is to simplify the TCA language configuration. It therefore supersedes
the `special=languages` option of TCA columns with `type=select` as well as the
now mis-use of the `foreign_table` option, being set to `sys_language`.

Since the introduction of site configurations and the corresponding site
languages back in v9, the `sys_language` table was not longer the only source
of thruth regarding available languages. Actually, the languages, available for
a record, are defined by the associated site configuration.

The new field therefore allows to finally decouple the actually available site
languages from the `sys_language` table. This effectively reduces quite an
amount of code and complexity, since no relations have to be fetched and
processed anymore. This also makes the `sys_refindex` table a bit smaller,
since no entries have to be added for this relation anymore. To clean up your
exisiting reference index, you might use the CLI command
:php:`bin/typo3 referenceindex:update`.

Another pain point was the special `-1` language which always had to be added
to each TCA configuration manually. Thus, a lot of different implementations
of this special case could be found in one and the same TYPO3 installation.

The new TCA type now automatically displays all available languages for the
current context (the corresponding site configuration) and also automatically
adds the special `-1` language for all record types, except `pages`.

.. code-block:: php

   // Before

   'config' => [
      'type' => 'select',
      'renderType' => 'selectSingle',
      'foreign_table' => 'sys_language',
      'items' => [
         ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
         ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
      ],
      'default' => 0
   ]

   // After

   'config' => [
      'type' => 'language'
   ]

.. code-block:: php

   // Before

   'config' => [
      'type' => 'select',
      'renderType' => 'selectSingle',
      'special' => 'languages',
      'items' => [
         [
            'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
            -1,
            'flags-multiple'
         ],
      ],
      'default' => 0,
   ]

   // After

   'config' => [
      'type' => 'language'
   ]

Since the new TCA type is mostly based on the `type=select` internally, most
of the associated TCA and TSconfig options can still be applied. This includes
e.g. the `selectIcons` field wizard, as well as the :typoscript:`keepItems`
and :typoscript:`removeItems` page TSconfig options.

In records on root level (`pid=0`) or on a page, outside of a site context,
all languages form all site configurations are displayed in the new field.

An automatic TCA migration is performed on the fly, migrating all occurences
to the new TCA type and adding a deprecation message to the deprecation log
where code adaption has to take place. Occurences are all columns, defined as
:php:`$TCA['ctrl']['languageField']`, as well as all columns, using the
`special=languages` option in combination with `type=select`.

Note that the migration resets the whole `config` array to use the new TCA
type. Custom setting such as field wizards are not evaluated until the TCA
configuration is adapted.

.. index:: Backend, PHP-API, TCA, ext:core
