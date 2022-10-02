.. include:: /Includes.rst.txt

.. _feature-97201:

=============================================================================
Feature: #97201 - PSR-14 event for modifying new content element wizard items
=============================================================================

See :issue:`97201`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent`
has been introduced which serves as a more powerful and flexible alternative
for the now removed hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']`.

The event is called after TYPO3 has already prepared the wizard items,
defined in TSconfig (:typoscript:`mod.wizards.newContentElement.wizardItems`).

The event allows listeners to modify any available wizard item as well
as adding new ones. It's therefore possible for the listeners to e.g. change
the configuration, the position or to remove existing items altogether.

Following methods are available:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| getWizardItems()        |                       | Returns all available wizard items.                |
+-------------------------+-----------------------+----------------------------------------------------+
| setWizardItems()        | :php:`$wizardItems`   | Updates / overwrites the available wizard items.   |
+-------------------------+-----------------------+----------------------------------------------------+
| hasWizardItem()         | :php:`$identifier`    | Whether a wizard item with the :php:`$identifier`  |
|                         |                       | exists.                                            |
+-------------------------+-----------------------+----------------------------------------------------+
| getWizardItem()         | :php:`$identifier`    | Returns the wizard item with the                   |
|                         |                       | :php:`$identifier` or :php:`null` if it does not   |
|                         |                       | exist.                                             |
+-------------------------+-----------------------+----------------------------------------------------+
| setWizardItem()         | :php:`$identifier`    | Add a new wizard item with the :php:`identifier`   |
|                         | :php:`$configuration` | and the :php:`$configuration` at the defined       |
|                         | :php:`$position`      | :php:`$position`. :php:`$position` is an `array`.  |
|                         |                       | Allowed values are `before => <identifier>` and    |
|                         |                       | `after => <identifier>`. Can also be used to       |
|                         |                       | modify or relocate existing items.                 |
+-------------------------+-----------------------+----------------------------------------------------+
| removeWizardItem()      | :php:`$identifier`    | Removes a wizard item with the :php:`$identifier`  |
+-------------------------+-----------------------+----------------------------------------------------+
| getPageInfo()           |                       | Provides information about the current page making |
|                         |                       | use of the wizard.                                 |
+-------------------------+-----------------------+----------------------------------------------------+
| getColPos()             |                       | Provides information about the column position     |
|                         |                       | of the button that triggered the wizard.           |
+-------------------------+-----------------------+----------------------------------------------------+
| getSysLanguage()        |                       | Provides information about the language used       |
|                         |                       | while triggering the wizard.                       |
+-------------------------+-----------------------+----------------------------------------------------+
| getUidPid()             |                       | Provides information about the element to position |
|                         |                       | the new element after (uid) or into (pid).         |
+-------------------------+-----------------------+----------------------------------------------------+

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Frontend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/backend/modify-wizard-items'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;

    class MyEventListener {

        public function __invoke(
            ModifyNewContentElementWizardItemsEvent $event
        ): void
        {
            // Add a new wizard item after "textpic"
            $event->setWizardItem(
                'my_element',
                [
                    'iconIdentifier' => 'icon-my-element',
                    'title' => 'My element',
                    'description' => 'My element description',
                    'tt_content_defValues' => [
                        'CType' => 'my_element'
                    ],
                ],
                ['after' => 'common_textpic']
            );
        }
    }

Impact
======

The main advantages of the new PSR-14 event are the object-oriented
approach as well as the built-in convenience features, like relocating
of the wizard items.

.. index:: Frontend, ext:frontend
