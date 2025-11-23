..  include:: /Includes.rst.txt

..  _feature-107526-1747816234:

=======================================================
Feature: #107526 - Custom TCA types for scheduler tasks
=======================================================

See :issue:`107526`

Description
===========

Scheduler tasks can now be created using custom TCA types instead of the
legacy :`AdditionalFieldProvider` approach. This enhancement allows
developers to define custom database fields for specific task types,
providing full flexibility of FormEngine and DataHandler for editing and
persistence.

The new approach replaces three major downsides of the traditional method:

1.  Native database fields are now created automatically via TCA instead of
    being serialized into a JSON field.
2.  Tasks are registered via TCA record types instead of custom registration
    methods.
3.  Field handling is now done through standard TCA configuration, eliminating
    the need for AdditionalFieldProviders.

Benefits
========

Using custom TCA types for scheduler tasks provides several advantages:

*   FormEngine handles validation automatically, reducing the risk of XSS or
    SQL injection vulnerabilities in extension code.
*   Task configuration follows standard TYPO3 form patterns.
*   Developers can use familiar TCA configuration instead of implementing
    custom field providers.
*   Access to all TCA field types, validation, and rendering options.

Migration
=========

Existing task types using custom TCA types automatically migrate existing
data through the :php:`getTaskParameters()` and :php:`setTaskParameters()`
methods:

*   During migration, :php:`getTaskParameters()` is called to extract field
    values from the serialized task object.
*   For new TCA-based tasks, :php:`setTaskParameters()` receives the full
    database record as an array instead of serialized data from the
    `parameters` field.
*   The task class name still matches the value of the :sql:`tasktype` field.

Implementation examples
=======================

File storage indexing task
--------------------------

TCA configuration in
:file:`Configuration/TCA/Overrides/file_storage_indexing_task.php`:

..  code-block:: php
    :caption: TCA configuration for the file storage indexing task

    defined('TYPO3') or die();

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
    use TYPO3\CMS\Scheduler\Task\FileStorageIndexingTask;

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'scheduler.messages:fileStorageIndexing.name',
            'description' => 'scheduler.messages:fileStorageIndexing.description',
            'value' => FileStorageIndexingTask::class,
            'icon' => 'mimetypes-x-tx_scheduler_task_group',
            'iconOverlay' => 'content-clock',
            'group' => 'scheduler',
        ],
        '
            --div--;core.form.tabs:general,
                tasktype,
                task_group,
                description,
                file_storage;scheduler.messages:label.fileStorageIndexing.storage,
            --div--;scheduler.messages:scheduler.form.palettes.timing,
                execution_details,
                nextexecution,
                --palette--;;lastexecution,
            --div--;core.form.tabs:access,
                disable,
            --div--;core.form.tabs:extended,',
        [],
        '',
        'tx_scheduler_task'
    );

Task class with migration support:

..  code-block:: php
    :caption: FileStorageIndexingTask class

    namespace TYPO3\CMS\Scheduler\Task;

    class FileStorageIndexingTask extends AbstractTask
    {
        public $storageUid = -1;

        public function execute()
        {
            // Task execution logic
            return true;
        }

        public function getTaskParameters(): array
        {
            return [
                'file_storage' => $this->storageUid,
            ];
        }

        public function setTaskParameters(array $parameters): void
        {
            $this->storageUid = $parameters['storageUid']
                ?? $parameters['file_storage']
                ?? 0;
        }
    }

Recycler cleaner task with custom fields
----------------------------------------

TCA configuration with custom field overrides in
:file:`Configuration/TCA/Overrides/scheduler_cleaner_task.php`:

..  code-block:: php
    :caption: TCA configuration for the recycler cleaner task

    defined('TYPO3') or die();

    use TYPO3\CMS\Recycler\Task\CleanerTask;

    if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
            [
                'label' => 'recycler.tasks:cleanerTaskTitle',
                'description' => 'recycler.tasks:cleanerTaskDescription',
                'value' => CleanerTask::class,
                'icon' => 'mimetypes-x-tx_scheduler_task_group',
                'iconOverlay' => 'content-clock',
                'group' => 'recycler',
            ],
            '
                --div--;core.form.tabs:general,
                    tasktype,
                    task_group,
                    description,
                    selected_tables;recycler.tasks:cleanerTaskTCA,
                    number_of_days;recycler.tasks:cleanerTaskPeriod,
                --div--;scheduler.messages:scheduler.form.palettes.timing,
                    execution_details,
                    nextexecution,
                    --palette--;;lastexecution,
                --div--;core.form.tabs:access,
                    disable,
                --div--;core.form.tabs:extended,',
            [
                'columnsOverrides' => [
                    'selected_tables' => [
                        'label' => 'recycler.tasks:cleanerTaskTCA',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectMultipleSideBySide',
                            'size' => 10,
                            'minitems' => 1,
                            'maxitems' => 100,
                            'itemsProcFunc' =>
                                CleanerTask::class. '->getAllTcaTables',
                            'items' => [],
                        ],
                    ],
                ],
            ],
            '',
            'tx_scheduler_task'
        );
    }

Task class with migration support:

..  code-block:: php
    :caption: CleanerTask class

    namespace TYPO3\CMS\Recycler\Task;

    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Scheduler\Task\AbstractTask;

    class CleanerTask extends AbstractTask
    {
        protected int $period = 0;

        protected array $tcaTables = [];

        public function execute()
        {
            // Task execution logic
            return true;
        }

        public function getAdditionalInformation()
        {
            $message = sprintf(
                $this->getLanguageService()->sL(
                    'recycler.tasks:cleanerTaskDescriptionTables'
                ),
                implode(', ', $this->tcaTables)
            );
            $message .= '; ';
            $message .= sprintf(
                $this->getLanguageService()->sL(
                    'recycler.tasks:cleanerTaskDescriptionDays'
                ),
                $this->period
            );
            return $message;
        }

        public function getTaskParameters(): array
        {
            return [
                'selected_tables' => implode(',', $this->tcaTables),
                'number_of_days' => $this->period,
            ];
        }

        public function setTaskParameters(array $parameters): void
        {
            $tcaTables = $parameters['RecyclerCleanerTCA']
                ?? $parameters['selected_tables']
                ?? [];
            if (is_string($tcaTables)) {
                $tcaTables = GeneralUtility::trimExplode(',', $tcaTables, true);
            }
            $this->tcaTables = $tcaTables;
            $this->period = (int)(
                $parameters['RecyclerCleanerPeriod']
                ?? $parameters['number_of_days']
                ?? 180
            );
        }
    }

Creating custom task types
==========================

To create a custom scheduler task with TCA configuration:

1.  Create the task class extending :php:`AbstractTask`.
    Implement the core logic in the :php:`execute()` method.

2.  Implement migration methods :php:`getTaskParameters()` and
    :php:`setTaskParameters()`.

3.  Optionally add :php:`getAdditionalInformation()` for backend listing
    output.

4.  Add TCA configuration in
    :file:`Configuration/TCA/Overrides/tx_scheduler_task_your_task.php`.

5.  Define custom fields in the TCA `showitem` configuration.

6.  Register the task type using
    :php:`ExtensionManagementUtility::addRecordType()`.

Example custom task TCA registration in
:file:`Configuration/TCA/Overrides/tx_scheduler_task_your_task.php`:

..  code-block:: php
    :caption: Example custom task TCA registration

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addTCAcolumns(
        'tx_scheduler_task',
        [
            'custom_field' => [
                'label' => 'my_extension.tasks:custom_field',
                'config' => [
                    'type' => 'input',
                ],
            ],
        ]
    );

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'My Custom Task',
            'description' => 'Description of what this task does',
            'value' => \MyVendor\MyExtension\Task\CustomTask::class,
            'icon' => 'my-custom-icon',
            'iconOverlay' => 'my-custom-icon-overlay',
            'group' => 'my_extension',
        ],
        '
            --div--;General,
                tasktype,
                task_group,
                description,
                custom_field,
                number_of_days;my_extension.tasks:myTaskPeriodLabel,
            --div--;Timing,
                execution_details,
                nextexecution,
                --palette--;;lastexecution,
            --div--;Access,
                disable,
        ',
        [
            'columnsOverrides' => [
                'number_of_days' => [
                    'config' => [
                        'eval' => 'required',
                    ],
                ],
            ],
        ],
        '',
        'tx_scheduler_task'
    );

..  tip::
    After migrating your tasks to native TCA types, run the **Migrate the
    contents of the tx_scheduler_task database table into a more structured
    form** upgrade wizard.

Impact
======

*   Extension developers can create more maintainable and secure scheduler
    tasks.
*   Custom task configuration benefits from full FormEngine capabilities.
*   Existing tasks are automatically migrated without data loss.
*   Development follows standard TYPO3 TCA patterns.
*   Improved validation and security through FormEngine and DataHandler.

..  index:: TCA, PHP-API, ext:scheduler
