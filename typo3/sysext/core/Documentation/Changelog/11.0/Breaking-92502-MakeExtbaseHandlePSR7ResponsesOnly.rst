.. include:: /Includes.rst.txt

===========================================================
Breaking: #92502 - Make Extbase handle PSR-7 responses only
===========================================================

See :issue:`92502`

Description
===========

Extbase does no longer handle/return extbase responses whose api was defined by the
interface :php:`TYPO3\CMS\Extbase\Mvc\ResponseInterface`. Instead, Extbase does create a `PSR-7`
compatible response object (see :php:`Psr\Http\Message\ResponseInterface`) and passes
it back through the request handling stack.

Since `PSR-7` requires response objects to be immutable, it no longer makes sense to expose the response object
to the user via :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$response`
and :php:`TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext->getResponse()`.


The following interface has been removed and is no longer usable:

- :php:`TYPO3\CMS\Extbase\Mvc\ResponseInterface`

The following class has been removed and is no longer usable:

- :php:`TYPO3\CMS\Extbase\Mvc\Response`


Impact
======

Since interface :php:`TYPO3\CMS\Extbase\Mvc\ResponseInterface` and  class :php:`TYPO3\CMS\Extbase\Mvc\Response`
have been removed, they can no longer be used.

Affected Installations
======================

All installations that:

* declared classes that implemented the interface :php:`TYPO3\CMS\Extbase\Mvc\ResponseInterface`
* instantiated or extended class :php:`TYPO3\CMS\Extbase\Mvc\Response`
* accessed the request object through :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$response` or :php:`TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext->getResponse()`

Migration
=========

To regain full control over the response object, a PSR-7 compatible response object SHOULD be created in the
controller action and returned instead of returning a string or void.

Example:

.. code-block:: php

   public function listAction()
   {
       // do your action stuff
       return $this->htmlResponse();
   }

.. note::

    If no argument is given to :php:`$this->htmlResponse()`, the current view
    is automatically rendered, and applied as content for the PSR-7 Response.
    For more information about this topic, please refer to the corresponding
    :doc:`changelog <../11.0/Deprecation-92784-ExtbaseControllerActionsMustReturnResponseInterface>`.

Further: Method :php:`TYPO3\CMS\Extbase\Mvc\Response::addAdditionalHeaderData()`
had been used to add additional header data such as css or js to the global TypoScriptFrontendController.
This has to be done via :php:`TYPO3\CMS\Core\Page\AssetCollector` now.

.. index:: PHP-API, NotScanned, ext:extbase
