.. include:: /Includes.rst.txt

.. _feature-107180-1759780285:

=========================================================
Feature: #107180 - PSR-14 events for Backend Users module
=========================================================

See :issue:`107180`

Description
===========

Several PSR-14 events have been added to allow for customizations of the
Backend Users module, in particular which users, groups and file mounts can
be viewed in the module.


AfterBackendUserListConstraintsAssembledFromDemandEvent
-------------------------------------------------------

This event is dispatched when the backend user repository fetches a list of
filtered backend users (itself called when displaying the list of users
in the backend module). It makes it possible to modify the query constraints
based on the currently active filtering.

The event provides the following public properties:

* :php:`$demand`: An instance of :php:`\TYPO3\CMS\Beuser\Domain\Model\Demand` containing the current
  search criteria
* :php:`$query`: The :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface` instance being used to
  assemble the query
* :php:`$constraints`: An array of query constraints. New constraints can be added to this array.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php

    <?php

    namespace MyVendor\MyPackage\EventListener;

    use TYPO3\CMS\Beuser\Event\AfterBackendUserListConstraintsAssembledFromDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AfterBackendUserListConstraintsAssembledFromDemandEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterBackendUserListConstraintsAssembledFromDemandEvent $event): void
        {
            $event->constraints[] = $event->query->eq('admin', 1);
        }
    }


AfterBackendGroupListConstraintsAssembledFromDemandEvent
--------------------------------------------------------

This event is dispatched when the backend group repository fetches a list of
filtered backend groups (itself called when displaying the list of groups
in the backend module). It makes it possible to modify the query constraints
based on the currently active filtering.

The event provides the following public properties:

* :php:`$demand`: An instance of :php:`\TYPO3\CMS\Beuser\Domain\Dto\BackendUserGroup` containing the current
  search criteria
* :php:`$query`: The :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface` instance being used to
  assemble the query
* :php:`$constraints`: An array of query constraints. New constraints can be added to this array.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php

    <?php

    namespace MyVendor\MyPackage\EventListener;

    use TYPO3\CMS\Beuser\Event\AfterBackendGroupListConstraintsAssembledFromDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AfterBackendUserGroupConstraintsAssembledFromDemandEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterBackendGroupListConstraintsAssembledFromDemandEvent $event): void
        {
            $event->constraints[] = $event->query->eq('workspace_perms', 1);
        }
    }


AfterBackendGroupFilterListIsAssembledEvent
-------------------------------------------

A list of user group can be used to filter the users list in the backend module.
This event is dispatched right after this list is assembled and makes it possible to
modify it.

The event provides the following public property:

* :php:`$request`: The current Extbase request object
* :php:`$backendGroups`: An array of backend groups.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php

    <?php

    namespace MyVendor\MyPackage\EventListener;

    use TYPO3\CMS\Beuser\Event\AfterBackendGroupFilterListIsAssembledEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AfterBackendGroupFilterListIsAssembledEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterBackendGroupFilterListIsAssembledEvent $event): void
        {
            array_pop($event->backendGroups);
        }
    }


AfterFilemountsListIsAssembledEvent
-----------------------------------

This event is dispatched when the file mounts list is fetched to display
in the backend module. It makes it possible to modify this list.

The event provides the following public property:

* :php:`$request`: The current Extbase request object
* :php:`$filemounts`: An array of file mounts.

Example
^^^^^^^

Here is an example event listener:

..  code-block:: php

    <?php

    namespace MyVendor\MyPackage\EventListener;

    use TYPO3\CMS\Beuser\Event\AfterFilemountsListIsAssembledEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AfterFilemountsListIsAssembledEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterFilemountsListIsAssembledEvent $event): void
        {
            array_pop($event->filemounts);
        }
    }


Impact
======

The events can be used, for example, to implement finer user management processes.
Be aware that this is a sensitive area in terms of security. Please ensure that you
are not introducing any breach of security when using these events, for example
revealing restricted information.

.. index:: Backend, ext:beuser
