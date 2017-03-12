.. include:: ../../Includes.txt

================================================================================
Feature: #12211 - Usability: Scheduler provide page browser to choose start page
================================================================================

See :issue:`12211`

Description
===========

To improve the usability of the linkvalidator scheduler task, the page browser is provided to choose the start page.


Impact
======

Scheduler tasks that need a page `uid` can now add a button for the page browser popup.

In the `ValidatorTaskAdditionalFieldProvider` two additional fields have to be added.

.. code-block:: php

   'browser' => 'page',

If the additional field `browser` is set to `page` then the `SchedulerModuleController` adds a button for calling the page browser popup to the field.

.. code-block:: php

   'pageTitle' => $pageTitle,

The `pageTitle` contains the title of the page that is shown next to the browse button.

.. index:: ext:scheduler, Backend