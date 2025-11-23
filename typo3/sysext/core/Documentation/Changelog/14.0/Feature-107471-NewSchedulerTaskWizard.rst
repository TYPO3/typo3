..  include:: /Includes.rst.txt

..  _feature-107471-1758106735:

============================================
Feature: #107471 - New scheduler task wizard
============================================

See :issue:`107471`

Description
===========

A wizard to create new scheduler tasks has been introduced in the
:guilabel:`Administration > Scheduler` module to significantly improve the user
experience when creating new scheduler tasks. The wizard replaces the previous
dropdown-based task selection in FormEngine with a modern, categorized
interface similar to the content element wizard.

.. note::
   The top-level backend modules were renamed in TYPO3 v14.
   The module now called :guilabel:`Administration` was formerly named
   :guilabel:`System`, and the module now called :guilabel:`System` was formerly
   named :guilabel:`Admin Tools`.

   For details, see:
   `Feature: #107628 – Improved backend module naming and structure
   <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

UX improvements
---------------

*   **Categorized task selection**: Tasks are now organized by extension or
    category for better discoverability
*   **Search functionality**: Users can search and filter available tasks
*   **Visual task representation**: Each task displays with proper icons,
    titles, and descriptions

..  tip::
    Using the :php:`iconOverlay` option on task type registration, an icon
    overlay can be added, which is then displayed in the wizard. This can be
    useful for similar task types that use the same base `icon` but still need
    to be differentiated.

Technical improvements
----------------------

*   **Prevents broken records**: The old system preselected the first available
    task type when creating new tasks, which caused validation issues when
    users changed the task type, since required fields for the new type might
    not be properly initialized
*   **Clean task type preselection**: The selected task type is directly passed
    to FormEngine, eliminating the need to change the type in the form
*   **Extensible via PSR-14 event**: Extensions can modify wizard items through
    the new
    :php-short:`\TYPO3\CMS\Scheduler\Event\ModifyNewSchedulerTaskWizardItemsEvent`

PSR-14 event
============

A new PSR-14 event
:php-short:`\TYPO3\CMS\Scheduler\Event\ModifyNewSchedulerTaskWizardItemsEvent`
has been introduced to allow extensions to modify the wizard items.

The event provides the following methods:

*   :php:`getWizardItems()`: Returns the current wizard items array
*   :php:`setWizardItems()`: Sets the complete wizard items array
*   :php:`addWizardItem()`: Adds a single wizard item
*   :php:`removeWizardItem()`: Removes a wizard item by key
*   :php:`getRequest()`: Returns the current server request

Example
=======

A corresponding event listener class:

..  code-block:: php
    :caption: Example event listener class

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Scheduler\Event\ModifyNewSchedulerTaskWizardItemsEvent;

    final class ModifySchedulerTaskWizardListener
    {
        #[AsEventListener('my-extension/scheduler/modify-wizard-items')]
        public function __invoke(ModifyNewSchedulerTaskWizardItemsEvent $event): void
        {
            // Add a custom task to the wizard
            $event->addWizardItem('my_custom_task', [
                'title' => 'My Custom Task',
                'description' => 'A custom task provided by my extension',
                'iconIdentifier' => 'my-custom-icon',
                'taskType' => 'MyVendor\\MyExtension\\Task\\CustomTask',
                'taskClass' => 'MyVendor\\MyExtension\\Task\\CustomTask',
            ]);

            // Remove an existing task
            $event->removeWizardItem('redirects_redirects:checkintegrity');

            // Modify existing wizard items
            $wizardItems = $event->getWizardItems();
            foreach ($wizardItems as $key => $item) {
                if (isset($item['title'])
                    && str_contains($item['title'], 'referenceindex:update')) {
                    $item['title'] = 'Update reference index';
                    $event->addWizardItem($key, $item);
                }
            }
        }
    }

Impact
======

*   The scheduler task creation workflow is significantly improved with better
    UX
*   The risk of creating broken task records due to task type changes is
    eliminated
*   Extensions can easily modify the wizard through the PSR-14 event
*   The interface is more consistent with other TYPO3 wizard interfaces
*   Task discovery is improved through categorization and search functionality

..  index:: Backend, PHP-API, UX, ext:scheduler
