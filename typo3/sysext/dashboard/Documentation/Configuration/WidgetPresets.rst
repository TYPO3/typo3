.. include:: /Includes.rst.txt

.. highlight:: php

.. _dashboard-presets:

=================
Dashboard Presets
=================

It is possible to configure presets of dashboards.
The extension already ships a ``default`` as well as an ``empty`` dashboard preset.

.. _create-preset:

Create preset
-------------

New presets can be configured via :file:`Configuration/Backend/DashboardPresets.php`::

   <?php

   return [
       'default' => [
           'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.default',
           'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.default.description',
           'iconIdentifier' => 'content-dashboard',
           'defaultWidgets' => ['t3information', 't3news', 'docGettingStarted'],
           'showInWizard' => false,
       ],
   ];

The file has to return an array with all presets.
Each prefix itself is an array, with an identifier as key.
The identifier is used to configure presets for users, see :ref:`configure-preset-for-user`.

Each preset consists of the following options:

.. program:: TYPO3\CMS\Dashboard\DashboardPreset

.. option:: title

   The title used for the preset. E.g. a ``LLL:EXT:`` reference..

.. option:: description

   The description used for the preset. E.g. a ``LLL:EXT:`` reference..

.. option:: iconIdentifier

   The identifier of the icon to use.

.. option:: defaultWidgets

   An array of widget identifiers, that should be part of the dashboard preset.

   Widgets are always filtered by permissions of each user.
   Only widgets with access are actually part of the dashboard.
   Have a look at :ref:`permission-handling-of-widgets` to understand how to handle permissions.

.. option:: showInWizard

   Boolean value to indicate, whether this preset should be visible in the wizard,
   when creating new dashboards, see :ref:`adding-dashboard`.

   This can be disabled, to add presets via :ref:`configure-preset-for-user`, without
   showing up in the wizard.

.. highlight:: typoscript
.. _configure-preset-for-user:

Configure preset for user
-------------------------

To define the default preset for a backend user, the following User TSconfig can be added::

   options.dashboard.dashboardPresetsForNewUsers = default

Where ``default`` is the identifier of the preset.
Even a comma separated list of identifiers is possible::

   options.dashboard.dashboardPresetsForNewUsers = default, companyDefault

It is also possible to add another dashboard to the set of dashboards::

   options.dashboard.dashboardPresetsForNewUsers := addToList(anotherOne)

If nothing is configured, ``default`` will be used as identifier.

.. seealso::

   :ref:`t3tsconfig:userthetsconfigfield` section of TSconfig manual
   explains how to set or register TSconfig for user.

   :ref:`t3coreapi:typoscript-syntax-syntax-value-modification` explains the usage of
   :typoscript:`:=` TypoScript operator.
