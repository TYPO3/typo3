.. include:: /Includes.rst.txt

.. highlight:: php

.. _create-widget-group:

===================
Create widget group
===================

Widget groups are used to group widgets into tabs.
This will have an effect when adding new widgets to an dashboard.
See :ref:`adding-widgets` to get an idea of the UI.

Groups are defines as PHP array in :file:`Configuration/Backend/DashboardWidgetGroups.php`::

   <?php

   return [
       'general' => [
           'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget_group.general',
       ],
       'systemInfo' => [
           'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget_group.system',
       ],
       'typo3' => [
           'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget_group.typo3',
       ],
       'news' => [
           'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget_group.news',
       ],
       'documentation' => [
           'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget_group.documentation',
       ],
   ];

The file has to return an array of groups.
Each group consists of an array key used as identifier and an single option :php:`title`.
The title will be processed through translation and can be an ``LLL`` reference.

Each extension can create arbitrary widget groups.

Widgets can be assigned to multiple groups using the :option:`groupNames`.
Please read :ref:`register-new-widget` to understand how this is done.
