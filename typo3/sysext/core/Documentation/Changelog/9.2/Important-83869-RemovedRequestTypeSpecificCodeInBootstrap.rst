.. include:: /Includes.rst.txt

===================================================================
Important: #83869 - Removed request type specific code in Bootstrap
===================================================================

See :issue:`83869`

Description
===========

All methods and properties related to specific HTTP or CLI handling in
:php:`\TYPO3\CMS\Core\Core\Bootstrap` have been removed.
These methods and properties were either protected or marked `@internal`.

Methods:

* :php:`redirectToInstallTool()`
* :php:`registerRequestHandlerImplementation()`
* :php:`resolveRequestHandler()`
* :php:`handleRequest()`
* :php:`sendResponse()`
* :php:`checkLockedBackendAndRedirectOrDie()`
* :php:`checkBackendIpOrDie()`
* :php:`checkSslBackendAndRedirectIfNeeded()`
* :php:`initializeOutputCompression()`
* :php:`sendHttpHeaders()`
* :php:`shutdown()`
* :php:`initializeBackendTemplate()`
* :php:`endOutputBufferingAndCleanPreviousOutput()`
* :php:`getApplicationContext()`
* :php:`getRequestId()`

Properties:

* :php:`protected $installToolPath;`
* :php:`protected $availableRequestHandlers`
* :php:`protected $response;`


Affected Installations
======================

All installations that use custom extensions that use request method specific methods of
:php:`\TYPO3\CMS\Core\Core\Bootstrap`.


Migration
=========

Custom request handlers that are registered using the internal method :php:`registerRequestHandlerImplementation()`
should be converted to PSR-15 middlewares. TYPO3 9.2 gained an API :file:`Configuration/Configuration/RequestMiddlewares.php`
for registering PSR-15 middleware HTTP handlers. See :php:`\TYPO3\CMS\Frontend\Middleware\EidHandler` for an example.

.. index:: Backend, CLI, Frontend, PHP-API, FullyScanned
