.. include:: /Includes.rst.txt

==================================================================
Feature: #98304 - PSR-14 Event for modifying edit form user access
==================================================================

See :issue:`98304`

Description
===========

A new PSR-14 Event :php:`\TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent`
has been introduced which serves as a more powerful and flexible alternative
for the now removed :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']`
hook.

In contrast to the removed hook does the new Event provide the full
database row of the record in question next to the exception, which
might have been set by the core. Additionally, the event allows to
modify the user access decision in an object-oriented way, using
convenience methods.

To modify the user access, the following methods are available:

- :php:`allowUserAccess()`: Allows user access to the editing form
- :php:`setLinkExplanation()`: Denies user access to the editing form
- :php:`doesUserHaveAccess()`: Returns the current user access state
- :php:`getAccessDeniedException()`: If core's DataProvider previously denied access, this returns the corresponding exception, :php:`null` otherwise

The following additional methods can be used for further context:

- :php:`getTableName()`: Returns the table name of the record in question
- :php:`getCommand()`: Returns the requested command, either `new` or `edit`
- :php:`getDatabaseRow()`: Returns the records database row

In case any listener to the new event denies user access, while it was initially
allowed by core, the :php:`TYPO3\CMS\Backend\Form\Exception\AccessDeniedListenerException`
will be thrown.

Example
=======

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Backend\Form\ModifyEditFormUserAccessEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/backend/modify-edit-form-user-access'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent;

    final class ModifyEditFormUserAccessEventListener
    {
        public function __invoke(ModifyEditFormUserAccessEvent $event): void
        {
            // Deny access for creating records of a custom table
            if ($event->getTableName() === 'my_custom_table' && $event->getCommand() === 'new') {
                $event->denyUserAccess();
            }
        }
    }

Impact
======

It's now possible to modify the user access for the editing form,
using the new PSR-14 :php:`ModifyLinkExplanationEvent`. The main
advantages of the new PSR-14 Event are the object-oriented approach
as well as the built-in convenience features and an increased amount
of context information.

.. index:: Backend, PHP-API, ext:backend
