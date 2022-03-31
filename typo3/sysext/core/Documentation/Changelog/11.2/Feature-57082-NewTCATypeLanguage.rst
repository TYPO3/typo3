.. include:: /Includes.rst.txt

=========================================
Feature: #57082 - New TCA type "language"
=========================================

See :issue:`57082`

Description
===========

A new TCA field type called :php:`language` has been added to TYPO3 Core. Its main
purpose is to simplify the TCA language configuration. It therefore supersedes
the :php:`special=languages` option of TCA columns with :php:`type=select` as well as the
now mis-use of the :php:`foreign_table` option, being set to :sql:`sys_language`.

Since the introduction of site configurations and the corresponding site
languages back in v9, the :sql:`sys_language` table was not longer the only source
of truth regarding available languages. The languages available for a record are
defined by the associated site configuration.

Therefore, the new field allows to finally decouple the available site
languages from the :sql:`sys_language` table. This effectively reduces quite an
amount of code and complexity, since no relations have to be fetched and
processed anymore. This also makes the :sql:`sys_refindex` table a bit smaller,
since no entries have to be added for this relation anymore. To clean up your
existing reference index, you might use the CLI command
:php:`bin/typo3 referenceindex:update`.

Another pain point was the special :php:`-1` language which always had to be added
to each TCA configuration manually. Thus, a lot of different implementations
of this special case could be found in one and the same TYPO3 installation.

The new TCA type now automatically displays all available languages for the
current context (the corresponding site configuration) and also automatically
adds the special :php:`-1` language for all record types, except :sql:`pages`.

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


Since the new TCA type is mostly based on the :php:`type=select` internally, most
of the associated TCA and TSconfig options can still be applied. This includes
e.g. the :php:`selectIcons` field wizard, as well as the :typoscript:`keepItems`
and :typoscript:`removeItems` page TSconfig options.

In records on root level (:sql:`pid=0`) or on a page, outside of a site context,
all languages from all site configurations are displayed in the new field.

An automatic TCA migration is performed on the fly, migrating all occurrences
to the new TCA type and triggering a PHP :php:`E_USER_DEPRECATED` error
where code adaption has to take place. Columns defined as
:php:`$TCA['ctrl']['languageField']`, as well as all columns using the
:php:`special=languages` option in combination with :php:`type=select` are
affected.

Note that the migration resets the whole :php:`config` array to use the new TCA
type. Custom setting such as field wizards are not evaluated until the TCA
configuration is adapted.

.. index:: Backend, PHP-API, TCA, ext:core
