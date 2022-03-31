.. include:: /Includes.rst.txt

============================================================
Breaking: #92853 - Method canProcessRequest has been removed
============================================================

See :issue:`92853`

Description
===========

Method :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->canProcessRequest()`
had been called to check if the currently passed in request could be handled by
the controller. This allowed to handle additional request types other than the
default one Extbase delivers. This would have only be useful if a user implemented
a request which didn't extend the Extbase request and therefore didn't necessarily
comply with its API. This however would have only been possible if the user
registered a custom request handler, violating the method signature of
:php:`TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface->handleRequest()`.

Back in 2012 this was an option to use Flow and Extbase interchangeably which was
never possible, and to allow Extbase Command Controllers via a CLI Request object,
which was removed in TYPO3 v10.

To unify the request/response handling and making it PSR-7 compatible, this check
has simply been removed along with its exception
:php:`\TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException`.

Impact
======

Actually very little if this feature had been used to have the controller handle
custom requests that extend the Extbase request. Custom requests with a different
api than the one needed by the framework will result in fatal errors.


Affected Installations
======================

All installations with Extbase controllers that have overridden property
:php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$supportedRequestTypes`
or method :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->canProcessRequest()`
and all installations which used :php:`\TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException`
in some way.


Migration
=========

There isn't that one code migration path. If you intend to extend (XClass) the
request object to add further properties/methods, you can still do so and nothing
actually changes. If you violated the api, implemented custom request builders
and handlers that handled requests with a different api than the one needed by
the framework, you will encounter fatal errors eventually.

.. index:: PHP-API, NotScanned, ext:extbase
