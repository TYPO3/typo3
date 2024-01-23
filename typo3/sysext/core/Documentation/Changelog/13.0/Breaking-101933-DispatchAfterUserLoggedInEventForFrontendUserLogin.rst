.. include:: /Includes.rst.txt

.. _breaking-101933-1695472624:

===========================================================================
Breaking: #101933 - Dispatch AfterUserLoggedInEvent for frontend user login
===========================================================================

See :issue:`101933`

Description
===========

The :php:`\TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent` PSR-14
event is now also dispatched for a successful frontend user login.


Impact
======

Listeners to the :php:`AfterUserLoggedInEvent` event should evaluate the
implementation type of the :php:`$user` property, if custom functionality
after a user login should be executed for backend login only.


Affected installations
======================

Installations with an event listener to the php:`AfterUserLoggedInEvent` PSR-14
event.


Migration
=========

If custom functionality in a listener to the :php:`AfterUserLoggedInEvent`
event should be executed for the backend user login only, a type check for the
:php:`$user` property must be added.

.. code-block:: php

   // Before
   public function __invoke(AfterUserLoggedInEvent $afterUserLoggedInEvent): void
   {
       // custom logic after backend user login
   }

   // After
   public function __invoke(AfterUserLoggedInEvent $afterUserLoggedInEvent): void
   {
       if ($afterUserLoggedInEvent->getUser() instanceof BackendUserAuthentication) {
           // custom logic after backend user login
       }
   }

.. index:: Backend, NotScanned, ext:backend
