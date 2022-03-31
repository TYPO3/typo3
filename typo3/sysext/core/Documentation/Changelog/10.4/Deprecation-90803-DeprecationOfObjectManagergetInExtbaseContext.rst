.. include:: /Includes.rst.txt

.. _changelog-Deprecation-90803-ObjectManagerGet:

===========================================================
Deprecation: #90803 - ObjectManager::get in Extbase context
===========================================================

See :issue:`90803`

Description
===========

To help understand the deprecation of :php:`$objectManager->get(Service::class)` let's first have a look at its domain: Dependency Injection
and its history as well as the culprits to deal with.

With the introduction of Extbase over one decade ago, a lot of modern software development paradigms have been introduced into TYPO3.
One of that paradigms is Dependency Injection (DI) which is an approach of handling dependencies different than the one the TYPO3 core followed ever since.

Given there is an EmailService class, which is responsible for sending emails, the usual approach of creating such a service was to create it
the moment it was needed. TYPO3 never used the :php:`new` keyword to create new objects, but :php:`GeneralUtility::makeInstance()`, which pretty much does the same thing.
So, one approach of creating dependencies is creating them in the current scope where the dependency is needed.

.. tip::

   As a rule of thumb, you can remember the following:
   Whenever you are creating dependencies yourself with :php:`new` or :php:`GeneralUtility::makeInstance()`, you are not using Dependency Injection.

Extbase introduced the concept of Dependency Injection (DI) which means, that all dependencies are declared in a way, that the dependency chain is known before runtime.
The most common way of implementing DI is to declare dependencies as constructor arguments. This means, in the scope of the current class, all dependencies are made visible as constructor arguments.
As those dependencies need to be created outside the current scope, a service container implementation is responsible for the creation and management of service instances.
Then, instead of calling :php:`new Service(...)`, the container needs to be queried for the needed service, e.g. by calling :php:`$container->get(Service::class)`.
This also assures that the container provide the requested services with their dependencies, as they are created the same way.

There is an service container in Extbase but it's not exposed to the public. Instead, there is the :php:`ObjectManager` class, which acts as a proxy for the container and also has a :php:`get` method, to query instances of services.

Exactly that :php:`get()` method is now deprecated in the extbase context because it should never be called directly.

The usual extbase context is a controller. All controllers are created by the object manager and therefore support DI. Whenever a dependency is needed in an extbase context,
instead of calling :php:`$objectManager->get(Service::class)`, the usual DI approaches have to be used. Those approaches are constructor, method and property injection.

Migration
---------

If you are using code similar to the following example, you should migrate to dependency injection:

.. code-block:: php

   class MainController
   {
       public function listAction()
       {
           $service = $this->objectManager->get(Service::class);
           $service->doSomething();
       }
   }


Examples how to use dependency injection:

Constructor Injection
^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

   class MainController
   {
       private $service;

       public function __construct(Service $service)
       {
           $this->service = $service;
       }

       public function listAction()
       {
           $this->service->doSomething();
       }
   }


.. tip::

   Constructor injection is the preferred type of injection for dependencies.


Method Injection
^^^^^^^^^^^^^^^^

.. code-block:: php

   class MainController
   {
       private $service;

       public function injectService(Service $service)
       {
           $this->service = $service;
       }

       public function listAction()
       {
           $this->service->doSomething();
       }
   }


Property Injection
^^^^^^^^^^^^^^^^^^

.. code-block:: php

   class MainController
   {
       /**
        * @var Service
        * @TYPO3\CMS\Extbase\Annotation\Inject
        */
       public $service;

       public function listAction()
       {
           $this->service->doSomething();
       }
   }


Unfortunately, there is even more to consider here. Dependencies usually are services and services are objects which are shareable. TYPO3 users might be more used to the term `Singleton`, which means,
that there is just one instance of a service during runtime which is shared across all scopes. Singletons are a great way to save resources but there is more to Singletons than just that.
To be able to share the same instance of a class across all scopes, the instance cannot store information about its state in its properties.
The idea of Singletons is to have an object that always behaves the same, no matter where it is used.

Let's have a look at classes that are no services. We can borrow the term prototype from the Java world. A commonly used prototype object is a model. Each instance of a model clearly has a different state and therefore a different functionality.
Those objects can theoretically be injected but it's very uncommon to do so. Still, in Extbase, instances of prototypes (e.g. instances of models, or other instances that hold state) are very often created with the object manager,
which is bad practice. :php:`new` or :php:`GeneralUtility::makeInstance()` should be used for instantiating prototypes.

However, when it comes to prototypes, there is a mechanic which cannot be implemented differently yet: the override of an implementation.

It means, that it's possible to tell the :php:`ObjectManager` to create an instance of a different class than the one which is requested.
One example of that is class :php:`TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend`, which can be fetched from the :php:`ObjectManager` by requesting an instance of the :php:`TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface` interface.
This feature should only be used for services as well but it is often used to override models of other extensions. For models you can either decide to simply instantiate via :php:`new`, or if you want to provide support for overwriting models
via XCLASSes configured in :file:`ext_localconf.php` (configuration variable: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']`) you may also use :php:`GeneralUtility::makeInstance()`.

.. tip::

   Conclusion:

   Singletons (services without state) should be provided by Dependency Injection wherever possible.

   To create prototypes (instances with state), use :php:`new` or :php:`GeneralUtility::makeInstance()`.

   :php:`ObjectManager->get()` must no longer be used.


Impact
======

There is no impact yet. No PHP :php:`E_USER_DEPRECATED` error is triggered in TYPO3 10. This will probably change in TYPO3 11.x.


Affected Installations
======================

All installations that use :php:`ObjectManager->get()` directly to create instances of dependencies in a scope that supports native Dependency Injection.


Migration
=========

As mentioned above, constructor, method or property injection must be used instead.

.. index:: PHP-API, NotScanned, ext:extbase
