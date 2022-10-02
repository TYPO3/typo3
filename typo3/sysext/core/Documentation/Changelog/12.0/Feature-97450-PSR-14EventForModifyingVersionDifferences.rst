.. include:: /Includes.rst.txt

.. _feature-97450:

================================================================
Feature: #97450 - PSR-14 event for modifying version differences
================================================================

See :issue:`97450`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Workspaces\Event\ModifyVersionDifferencesEvent`
has been introduced which serves as a direct replacement for the now removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['modifyDifferenceArray']`
:doc:`hook <../12.0/Breaking-97450-RemovedHookForModifyingVersionDifferences>`.

It can be used to modify the version differences data, used for the display
in the :guilabel:`Workspaces` backend module. Those data can be accessed with
the :php:`getVersionDifferences()` method, and updated using
the :php:`setVersionDifferences(array $versionDifferences)` method.

The version differences :php:`array` contains the differences of each field,
with the following keys:

- :php:`field`: The corresponding field name,
- :php:`label`: The corresponding fields' label,
- :php:`content`: The field values difference

Furthermore does the event provide the following methods

- :php:`getLiveRecordData()`: Returns the records live data (used to create the version difference)
- :php:`getParameters()`: Returns meta information like current stage and current workspace

..  note::
    The removed hook allowed to update the live record data. This however had
    no effect since those data are not further used by TYPO3. Therefore, the
    new event does no longer provide a setter for the live record data.

..  note::
    The removed hook contained an instance of :php:`DiffUtility`, which can
    be used to generate the differences :php:`string`. Since PSR-14 events
    are usually pure data objects, without dependencies to any service, the
    new PSR-14 event does no longer provide an instance of :php:`DiffUtility`.
    Listeners have to inject the service on their own - if needed.

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Workspaces\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/workspaces/modify-version-differences'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Core\Utility\DiffUtility;
    use TYPO3\CMS\Workspaces\Event\ModifyVersionDifferencesEvent;

    final class MyEventListener
    {
        public function __construct(protected readonly DiffUtility $diffUtility)
        {
            $this->diffUtility->stripTags = false;
        }

        public function __invoke(ModifyVersionDifferencesEvent $event): void
        {
            $differences = $event->getVersionDifferences();
            foreach($differences as $key => $difference) {
                if ($difference['field'] === 'my_test_field') {
                    $differences[$key]['content'] = $this->diffUtility->makeDiffDisplay('a', 'b');
                }
            }

            $event->setVersionDifferences($differences);
        }
    }

Impact
======

It's now possible to modify the version differences of a versioned record,
using the new PSR-14 event :php:`ModifyVersionDifferencesEvent`.

.. index:: Backend, PHP-API, ext:backend
