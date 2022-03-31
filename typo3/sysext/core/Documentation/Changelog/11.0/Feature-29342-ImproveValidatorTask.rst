.. include:: /Includes.rst.txt

=======================================
Feature: #29342 - Improve ValidatorTask
=======================================

See :issue:`29342`

Description
===========

The :php:`\TYPO3\CMS\Linkvalidator\Task\ValidatorTask` scheduler task for
reporting broken links via email which still used marker templates, has been
improved. This is achieved by switching to `FluidEmail`, extending the task
configuration along with the mails content for more detailed reports, code
refactoring and introduction of strict types for both

:php:`\TYPO3\CMS\Linkvalidator\Task\ValidatorTask` and
:php:`\TYPO3\CMS\Linkvalidator\Task\ValidatorTaskAdditionalFieldProvider`.

The task configuration got the following new fields:

* `languages` Comma separated list of language uids
* `emailTemplateName` Name of the fluid template

With `languages` it's now possible to limit the report to specified system
languages. This is useful if multiple tasks for different groups of recipients
should be registered.

With `emailTemplateName` it is possible to use different custom templates for each
task. The template path must be set in :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']`. Additionally
the used `SystemEmail` layout can be changed by setting your custom layout
path in :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths']`. If no
`emailTemplateName` is set or the specified input is invalid, the task
automatically uses the default template name on task execution.

The following new PSR-14 event has been introduced:

:php:`\TYPO3\CMS\Linkvalidator\Event\ModifyValidatorTaskEmailEvent`

This event can be used to manipulate the :php:`\TYPO3\CMS\Linkvalidator\Result\LinkAnalyzerResult`,
which contains all information from the linkvalidator API. Also the `FluidEmail`
object can be adjusted here. This allows to e.g. pass additional information to
the view by using :php:`$fluidEmail->assign()` or dynamically adding mail information
such as the receivers list. The added values in the event take precedence over the
:typoscript:`modTSconfig` configuration. The event contains the full :typoscript:`modTSconfig`
to access further information about the actual configuration of the task when
assigning new values to `FluidEmail`.

Note: As it's now also possible to set the recipient addresses dynamically using
the event, the `email` field in the task configuration can remain empty but will
be added, if defined, on top of already defined recipients from the event. All
other values such as `subject`, `from` or `replyTo` will only be set according to
`modTSconfig` if not already defined through the event.

An example implementation of the PSR-14 event:

.. code-block:: php

   <?php
   declare(strict_types=1);
   namespace Vendor\Extension\EventListener;

   use TYPO3\CMS\Linkvalidator\Event\ModifyValidatorTaskEmailEvent;

   class ModifyValidatorTaskEmail
   {
      public function modify(ModifyValidatorTaskEmailEvent $event): void
      {
         $linkAnalyzerResult = $event->getLinkAnalyzerResult();
         $fluidEmail = $event->getFluidEmail();
         $modTSconfig = $event->getModTSconfig();

         if ($modTSconfig['mail.']['fromname'] === 'John Smith') {
            $fluidEmail->assign('myAdditionalVariable', 'foobar');
         }

         $fluidEmail->subject(
            $linkAnalyzerResult->getTotalBrokenLinksCount() . ' new broken links'
         );

         $fluidEmail->to(new Address('custom@mail.com'));
      }
   }

.. code-block:: yaml

   Vendor\Extension\EventListener\ModifyValidatorTaskEmail:
      tags:
         - name: event.listener
         identifier: 'modify-validation-task-email'
         event: TYPO3\CMS\Linkvalidator\Event\ModifyValidatorTaskEmailEvent
         method: 'modify'

The :php:`\TYPO3\CMS\Linkvalidator\Result\LinkAnalyzerResult` contains following
information by default:

* :php:`$oldBrokenLinkCounts` Amount of broken links from the last run, separated by type (e.g. all, internal)
* :php:`$newBrokenLinkCounts` Amount of broken links from this run, separated by type (e.g. all, internal)
* :php:`$brokenLinks` List of broken links with the raw database row
* :php:`$differentToLastResult` Whether the broken links count changed

The :php:`brokenLinks` property gets further processed internally to provide additional
information for the email. Following additional information is provided by default:

* :php:`full_record` The full record, the broken link was found in (e.g. pages or tt_content)
* :php:`record_title` Value of the :php:`full_record` title field
* :php:`record_type` The title of the record type (e.g. "Page" or "Page Content")
* :php:`language_code` The language code of the broken link
* :php:`real_pid` The real page id of the record the broken link was found in
* :php:`page_record` The whole page row of records parent page

More can be added using the PSR-14 event.

Additionally to the already existing content the email now includes a list of all
broken links fetched according to the task configuration. This list consists of
following columns:

* `Record` The :php:`record_uid` and :php:`record_title`
* `Language` The :php:`language_code` and language id
* `Page` The :php:`real_pid` and :php:`page_record.title` of the parent page
* `Record Type` The :php:`record_type`
* `Link Target` The :php:`target`
* `Link Type` Type of the broken link (Either `internal`, `external` or `file`)


Impact
======

The main improvement is the more detailed report which is delivered by `FluidEmail`,
using the default `SystemEmail` layout. Along with the new PSR-14 event, extension authors
are now able to fully customize the content of the report as needed.

.. index:: Backend, CLI, NotScanned, ext:linkvalidator
