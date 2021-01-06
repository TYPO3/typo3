.. include:: ../../Includes.txt

==============================================================================
Deprecation: #92784 - Extbase controller actions must return ResponseInterface
==============================================================================

See :issue:`92784`

Description
===========

Until now, Extbase controller actions could return either nothing (void), null, a string, or an object that implements :php:`__toString()`.

From now on Extbase expects actions to return an instance of :php:`Psr\Http\Message\ResponseInterface`.


Impact
======

All actions that do not return an instance of :php:`Psr\Http\Message\ResponseInterface` trigger a PHP :php:`E_USER_DEPRECATED` error and will fail as of TYPO3 v12.


Affected Installations
======================

All installations that use Extbase controller actions which don't return an instance of :php:`Psr\Http\Message\ResponseInterface`.


Migration
=========

Since the core follows not only PSR-7 (https://www.php-fig.org/psr/psr-7/)
but also PSR-17 (https://www.php-fig.org/psr/psr-17/),
the PSR-17 response factory should be used.
The response factory is available in all extbase controllers and can be used as a shorthand function to create responses for html and json,
the two most used content types. The factory can also be used to create a blank response object whose content and headers can be set freely.

Example:

.. code-block:: php

   public function listAction(): ResponseInterface
   {
       $items = $this->itemRepository->findAll();
       $this->view->assign('items', $items);

       $response = $this->responseFactory->createResponse()
           ->withAddedHeader('Content-Type', 'text/html; charset=utf-8');
       $response->getBody()->write($this->view->render());
       return $response;
   }

This example only shows the most common use case. It causes html with a :html:`Content-Type: text/html` header and
http code `200 Ok` returned as the response to the client.

.. tip::

   Using the factory is a clean architectural solution but it's a lot of new code when migration from returning nothing at all.
   To ease the migration path, method :php:`htmlResponse(string $html = null)` has been introduced which allows for a quite small change.
   When called without an argument, said method renders the current view.

   .. code-block:: php

      public function listAction(): ResponseInterface
      {
          $items = $this->itemRepository->findAll();
          $this->view->assign('items', $items);

          return $this->htmlResponse();
      }


Of course you are free to adjust this response object before returning it.

Example:

.. code-block:: php

   public function listAction(): ResponseInterface
   {
       $items = $this->itemRepository->findAll();
       $this->view->assign('items', $items);

       $response = $this->responseFactory
           ->createResponse()
           ->withHeader('Cache-Control', 'must-revalidate')
           ->withHeader('Content-Type', 'text/html; charset=utf-8')
           ->withStatus(200, 'Super ok!');
       $response->getBody()->write($this->view->render());
       return $response;
   }

.. tip::

   Since Extbase uses PSR-7 responses, you should make yourself familiar with its API.
   Documentation and more information regarding PSR-7 responses can be found here: https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface


.. index:: PHP-API, NotScanned, ext:extbase
