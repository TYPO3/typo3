.. include:: /Includes.rst.txt

.. _breaking-98370-1663513316:

========================================================
Breaking: #98370 - Extbase Request cleanup and hardening
========================================================

See :issue:`98370`

Description
===========

Extbase :php:`\TYPO3\CMS\Extbase\Mvc\Request` has been turned into a decorator of the
PSR-7 :php:`ServerRequestInterface` :doc:`with Core v11 <../11.3/Feature-94428-ExtbaseRequestImplementsServerRequestInterface>`:
Extbase-based extensions work with the PSR-7 Core Request, Extbase
specific Request state is attached as an attribute to the PSR-7 Request.

Most of these Extbase-specific attribute properties are now available in Core v12
by activating the according decorator methods in :php:`\TYPO3\CMS\Extbase\Mvc\RequestInterface`,
which is implemented by :php:`\TYPO3\CMS\Extbase\Mvc\Request`. The :php:`RequestInterface` now
also properly extends PSR-7 :php:`ServerRequestInterface` and is type-hinted within
the Extbase framework.

PSR-7 interfaces rely on object immutability: A created Request object is never changed, instead
a new object is created and returned when changed. The old fashioned Extbase Request violated this with
various :php:`setXY()` methods. These have been removed, and this is the part that is considered
breaking for consuming extensions.

Impact
======

Extbase-based extensions using static code analyzers like phpstan in CI
should benefit from improved scanner results and can further harden their
codebase.

Affected installations
======================

Extensions that actively manipulate the given Extbase :php:`Request` using setter methods
will trigger fatal PHP "method does not exist" errors.

Instances with extensions actively creating a :php:`Request` must hand over
a :php:`\Psr\Http\Message\ServerRequestInterface` as constructor argument, it must have
the attribute :php:`extbase` set, which must be an instance of
:php:`\TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters`.

Migration
=========

It is relatively seldom that Extbase extensions need to actively manipulate the Extbase
Request since most of that is handled by the Extbase Framework internally for consuming
extensions.

Extensions that use the :php:`setXY()` methods for whatever reasons have to
change them to their :php:`withXY()` counterparts, though: Nearly all "withers" are now
declared as part of :php:`RequestInterface` and already exist in v11, "setters" can be
migrated quite easily.

From an Extbase Framework API point of view, extension should *only* rely on :php:`RequestInterface`
methods: The second level :php:`ExtbaseRequestParameters` attribute is considered
:php:`@internal` and extensions shouldn't work with it directly. There are just a couple
of methods used by Extbase Framework based on direct :php:`ExtbaseRequestParameters` manipulation,
most of them are related to the action argument validation and action forwarding behavior of Extbase,
which Extbase extensions in general shouldn't need to deal with themselves.

When changing from "setters" to "withers", the important key change is that calling a
:php:`withXY()` method *does not* manipulate the existing request, but *returns a new*
instance instead. In practice, if the previous Request object has been set to some other
client object beforehand, and if a new Request is created using a :php:`withXY()`
method, those client objects may need to be updated with the new object. A typical use case
is :php:`$this->view` in a controller class, which may now need :php:`$this->view->setRequest($myNewRequest)`
to receive the new :php:`Request` to work on, and it's most likely also a good idea to update
:php:`$this->request = $myNewRequest` as well.

.. index:: PHP-API, NotScanned, ext:extbase
