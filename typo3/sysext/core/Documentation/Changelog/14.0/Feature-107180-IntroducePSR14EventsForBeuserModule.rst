..  include:: /Includes.rst.txt

..  _feature-107180-1759780285:

=========================================================
Feature: #107180 - PSR-14 events for Backend Users module
=========================================================

See :issue:`107180`

Description
===========

Several PSR-14 events have been added to allow customizations of the Backend
Users module - in particular, which users, groups, and file mounts can be viewed
in the module.

AfterBackendUserListConstraintsAssembledFromDemandEvent
-------------------------------------------------------

This event is dispatched when the backend user repository fetches a list of
filtered backend users (itself called when displaying the list of users in the
backend module). It makes it possible to modify the query constraints based on
the currently active filtering.

The event provides the following public properties:

* :php:`$demand`: An instance of
  :php:`\TYPO3\CMS\Beuser\Domain\Model\Demand` containing the current search
  criteria
* :php:`$query`: The
  :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface` instance being used to
  assemble the query
* :php:`$constraints`: An array of query constraints. New constraints can be
  added to this array.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Beuser\Event\AfterBackendUserListConstraintsAssembledFromDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class MyEventListener
    {
        #[AsEventListener]
        public function __invoke(
            AfterBackendUserListConstraintsAssembledFromDemandEvent $event
        ): void {
            $event->constraints[] = $event->query->eq('admin', 1);
        }
    }

AfterBackendGroupListConstraintsAssembledFromDemandEvent
--------------------------------------------------------

This event is dispatched when the backend group repository fetches a list of
filtered backend groups (itself called when displaying the list of groups in the
backend module). It makes it possible to modify the query constraints based on
the currently active filtering.

The event provides the following public properties:

* :php:`$demand`: An instance of
  :php:`\TYPO3\CMS\Beuser\Domain\Dto\BackendUserGroup` containing the current
  search criteria
* :php:`$query`: The
  :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface` instance being used to
  assemble the query
* :php:`$constraints`: An array of query constraints. New constraints can be
  added to this array.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Beuser\Event\AfterBackendGroupListConstraintsAssembledFromDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class MyEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterBackendGroupListConstraintsAssembledFromDemandEvent $event): void
        {
            $event->constraints[] = $event->query->eq('workspace_perms', 1);
        }
    }

AfterBackendGroupFilterListIsAssembledEvent
-------------------------------------------

A list of user groups can be used to filter the users list in the backend
module. This event is dispatched right after this list is assembled and makes it
possible to modify it.

The event provides the following public properties:

* :php:`$request`: The current Extbase request object
* :php:`$backendGroups`: An array of backend groups.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Beuser\Event\AfterBackendGroupFilterListIsAssembledEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class MyEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterBackendGroupFilterListIsAssembledEvent $event): void
        {
            array_pop($event->backendGroups);
        }
    }

AfterFilemountsListIsAssembledEvent
-----------------------------------

This event is dispatched when the file mounts list is fetched to display in the
backend module. It makes it possible to modify this list.

The event provides the following public properties:

*   :php:`$request`: The current Extbase request object
*   :php:`$filemounts`: An array of file mounts.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php

    namespace MyVendor\MyExtension\EventListener;;

    use TYPO3\CMS\Beuser\Event\AfterFilemountsListIsAssembledEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class MyEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterFilemountsListIsAssembledEvent $event): void
        {
            array_pop($event->filemounts);
        }
    }

Impact
======

These events can be used to implement custom user or permission management
processes in the Backend Users module. Be aware that this area is security
sensitive. Ensure that no unauthorized data exposure or privilege escalation
occurs when modifying these queries or lists.

..  index:: Backend, ext:beuser
