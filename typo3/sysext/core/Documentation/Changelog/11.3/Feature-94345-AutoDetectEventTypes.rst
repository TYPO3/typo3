.. include:: /Includes.rst.txt

=========================================
Feature: #94345 - Auto-detect event types
=========================================

See :issue:`94345`

Description
===========

If no "event" tag is specified on an event listener in Services.yaml, the
event is automatically derived from the event method itself using reflection.


Impact
======

In the vast majority of cases, the "event" tag on an event listener in Services.yaml
is no longer necessary.

Given this example event listener implementation:

.. code-block:: php

   final class CategoryPermissionsAspect
   {
       public function addUserPermissionsToCategoryTreeData(ModifyTreeDataEvent $event): void
       {
          // ...
       }
   }

With this registration:

.. code-block:: yaml

   TYPO3\CMS\Backend\Security\CategoryPermissionsAspect:
       tags:
         - name: event.listener
           identifier: 'backend-user-permissions'
           method: 'addUserPermissionsToCategoryTreeData'
           event: TYPO3\CMS\Core\Tree\Event\ModifyTreeDataEvent


The :yaml:`event:` tag can be omitted, since it's automatically read from
the method signature :php:`addUserPermissionsToCategoryTreeData(ModifyTreeDataEvent $event)`
of the listener implementation:

.. code-block:: yaml

   TYPO3\CMS\Backend\Security\CategoryPermissionsAspect:
       tags:
         - name: event.listener
           identifier: 'backend-user-permissions'
           method: 'addUserPermissionsToCategoryTreeData'


.. index:: PHP-API, ext:core
