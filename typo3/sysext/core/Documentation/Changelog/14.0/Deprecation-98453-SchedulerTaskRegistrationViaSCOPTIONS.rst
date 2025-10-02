..  include:: /Includes.rst.txt

..  _deprecation-98453-1738408355:

================================================================
Deprecation: #98453 - Scheduler task registration via SC_OPTIONS
================================================================

See :issue:`98453`

Description
===========

The registration of scheduler tasks via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']`
has been deprecated in favor of the new native scheduler task feature using TCA.

Previously, scheduler tasks were registered in :file:`ext_localconf.php`
using the following syntax:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][MyTask::class] = [
        'extension' => 'my_extension',
        'title' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.title',
        'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.description',
        'additionalFields' => MyTaskAdditionalFieldProvider::class,
    ];

This approach required a separate :php:`AdditionalFieldProvider` class
to handle custom task fields. The AdditionalFieldProvider was responsible for:

* Rendering form fields in the scheduler module
* Validating field input
* Saving and loading field values

The new approach replaces this with native TCA configuration, providing:

* Better integration with TYPO3's FormEngine
* Automatic validation through TCA field configuration
* Enhanced security through FormEngine's XSS protection
* Consistency with other TYPO3 backend forms
* Access to all TCA field types and rendering options

In addition, the :php:`AbstractAdditionalFieldProvider` class and the generic
interface :php:`AdditionalFieldProviderInterface` have been deprecated.

Impact
======

Using :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']`
for registering scheduler tasks will stop working in TYPO3 v15.0.

Custom task classes implementing :php:`AdditionalFieldProviderInterface`
should remove this interface implementation. The interface methods
(:php:`getAdditionalFields()`, :php:`validateAdditionalFields()`,
:php:`saveAdditionalFields()`) are no longer needed with the new TCA-based
approach.


Affected installations
======================

All installations with custom scheduler tasks registered via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']`
and using the :php:`AdditionalFieldProviderInterface`.


Migration
=========

Scheduler tasks should now be registered as native task types using TCA.
This provides a more integrated and maintainable approach to task configuration.

Migration steps:

1. Remove the registration from :file:`ext_localconf.php`
2. Create a TCA override file in :file:`Configuration/TCA/Overrides/scheduler_my_task_type.php`
3. Update your task class to implement the new parameter methods
4. Remove the :php:`AdditionalFieldProvider` class if it exists

..  note::
    The new TCA-based approach automatically migrates existing task data.
    When upgrading, existing task configurations are preserved through the
    :php:`getTaskParameters()` and :php:`setTaskParameters()` methods.

Example migration
-----------------

Before:

..  code-block:: php
    :caption: ext_localconf.php

    use MyVendor\MyExtension\Task\MyTask;
    use MyVendor\MyExtension\Task\MyTaskAdditionalFieldProvider;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][MyTask::class] = [
        'extension' => 'my_extension',
        'title' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.title',
        'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.description',
        'additionalFields' => MyTaskAdditionalFieldProvider::class,
    ];

After:

..  code-block:: php
    :caption: Configuration/TCA/Overrides/scheduler_my_task_type.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
    use MyVendor\MyExtension\Task\MyTask;

    defined('TYPO3') or die();

    if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        // Add custom fields to the tx_scheduler_task table
        ExtensionManagementUtility::addTCAcolumns(
            'tx_scheduler_task',
            [
                'my_extension_field' => [
                    'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:field.label',
                    'config' => [
                        'type' => 'input',
                        'size' => 30,
                        'required' => true,
                        'eval' => 'trim', // FormEngine validation replaces custom validation
                        'placeholder' => 'Enter value here...',
                    ],
                ],
                'my_extension_email_list' => [
                    'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:emailList.label',
                    'config' => [
                        'type' => 'text',
                        'rows' => 3,
                        'required' => true, // 'required' validation handled by FormEngine
                        'placeholder' => 'admin@example.com',
                    ],
                ],
            ]
        );

        // Register the task type
        ExtensionManagementUtility::addRecordType(
            [
                'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.title',
                'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.description',
                'value' => MyTask::class,
                'icon' => 'mimetypes-x-tx_scheduler_task_group',
                'group' => 'my_extension',
            ],
            '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                tasktype,
                task_group,
                description,
                my_extension_field,
                my_extension_email_list,
            --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
                execution_details,
                nextexecution,
                --palette--;;lastexecution,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                disable,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
            [],
            '',
            'tx_scheduler_task'
        );
    }

Update your (existing) task class to implement the new methods:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Task/MyTask.php

    namespace MyVendor\MyExtension\Task;

    use TYPO3\CMS\Core\Messaging\FlashMessage;
    use TYPO3\CMS\Core\Messaging\FlashMessageService;
    use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Scheduler\Task\AbstractTask;

    class MyTask extends AbstractTask
    {
        protected string $myField = '';
        protected string $emailList = '';

        public function execute(): bool
        {
            // Your task logic here using $this->myField and $this->emailList
            return true;
        }

        /**
         * Return current field values as associative array.
         * This method is called during migration from old serialized tasks
         * and when displaying task information.
         */
        public function getTaskParameters(): array
        {
            return [
                'my_extension_field' => $this->myField,
                'my_extension_email_list' => $this->emailList,
            ];
        }

        /**
         * Set field values from associative array.
         * This method handles both old and new parameter formats for migration.
         *
         * @param array $parameters Values from either old AdditionalFieldProvider or new TCA fields
         */
        public function setTaskParameters(array $parameters): void
        {
            // Handle migration: check old parameter names first, then new TCA field names
            $this->myField = $parameters['myField'] ?? $parameters['my_extension_field'] ?? '';
            $this->emailList = $parameters['emailList'] ?? $parameters['my_extension_email_list'] ?? '';
        }

        /**
         * Validate task parameters.
         * Only implement this method for validation that cannot be handled by FormEngine.
         * Basic validation like 'required' should be done via TCA 'eval' configuration.
         */
        public function validateTaskParameters(array $parameters): bool
        {
            $isValid = true;

            // Example: Custom email validation (beyond basic 'required' check)
            $emailList = $parameters['my_extension_email_list'] ?? '';
            if (!empty($emailList)) {
                $emails = GeneralUtility::trimExplode(',', $emailList, true);
                foreach ($emails as $email) {
                    if (!GeneralUtility::validEmail($email)) {
                        GeneralUtility::makeInstance(FlashMessageService::class)
                            ->getMessageQueueByIdentifier()
                            ->addMessage(
                                GeneralUtility::makeInstance(
                                    FlashMessage::class,
                                    'Invalid email address: ' . $email,
                                    '',
                                    ContextualFeedbackSeverity::ERROR
                                )
                            );
                        $isValid = false;
                    }
                }
            }

            return $isValid;
        }

        public function getAdditionalInformation(): string
        {
            $info = [];
            if ($this->myField !== '') {
                $info[] = 'Field: ' . $this->myField;
            }
            if ($this->emailList !== '') {
                $info[] = 'Emails: ' . $this->emailList;
            }
            return implode(', ', $info);
        }
    }

Key methods explained
---------------------

The new TCA-based approach uses three key methods for parameter handling:

**getTaskParameters(): array**
    This method is already implemented in ``AbstractTask`` to handle task class
    properties automatically, but can be overridden in task classes for custom
    behavior.

    The method is primarily used:

    * For migration from old serialized task format to new TCA structure
    * For non-native (deprecated) task types to store their values in the legacy ``parameters`` field

    For native TCA tasks, this method is typically no longer needed in custom
    tasks after the migration has been done, since field values are then stored
    directly in database columns.

**setTaskParameters(array $parameters): void**
    Sets field values from an associative array. This method handles:

    * Migration from old AdditionalFieldProvider field names to new TCA field names
    * Loading saved task configurations when editing or executing tasks
    * Parameter mapping during task creation and updates
    * The method should always be implemented, especially for native tasks

    The migration pattern is: :php:`$this->myField = $parameters['oldName'] ?? $parameters['new_tca_field_name'] ?? '';`

**validateTaskParameters(array $parameters): bool**
    *Optional method.* Only implement this for validation that cannot be handled by FormEngine.

    * Basic validation (required, trim, etc.) should be done via TCA configuration (``required`` property and ``eval`` options)
    * Use this method for complex business logic validation (e.g., email format validation, external API checks)
    * Return ``false`` and add FlashMessage for validation errors
    * FormEngine automatically handles standard TCA validation rules

For a complete working example, see :php:`\TYPO3\CMS\Reports\Task\SystemStatusUpdateTask`
and its corresponding TCA configuration in
:file:`EXT:reports/Configuration/TCA/Overrides/scheduler_system_status_update_task.php`.


..  index:: PHP-API, NotScanned, ext:scheduler
