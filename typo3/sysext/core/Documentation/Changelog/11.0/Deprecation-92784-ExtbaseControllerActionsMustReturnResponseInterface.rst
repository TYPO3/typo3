.. include:: /Includes.rst.txt

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
the PSR-17 factories should be used. Both the :php:`$responseFactory` as
well as the :php:`$streamFactory` are available in all extbase controllers.
The :php:`$responseFactory` can be used to create a blank response object
whose content and headers can be set freely. The content can therfore be
set using the :php:`$streamFactory`.

Example:

.. code-block:: php

   public function listAction(): ResponseInterface
   {
       $items = $this->itemRepository->findAll();
       $this->view->assign('items', $items);

       return $this->responseFactory->createResponse()
           ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
           ->withBody($this->streamFactory->createStream($this->view->render()));
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

       return $this->responseFactory
           ->createResponse()
           ->withHeader('Cache-Control', 'must-revalidate')
           ->withHeader('Content-Type', 'text/html; charset=utf-8')
           ->withStatus(200, 'Super ok!')
           ->withBody($this->streamFactory->createStream($this->view->render()));
   }

.. tip::

    To adjust the content of an already created PSR-7 response object,
    :php:`$response->getBody()->write()` can be used.

.. tip::

   Since Extbase uses PSR-7 responses, you should make yourself familiar with its API.
   Documentation and more information regarding PSR-7 responses can be found here: https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface

In case you are using the :php:`JsonView` in your extbase controller, you may
want to ease the migration path with the new :php:`jsonResponse(string $json = null)`
method. Similar to :php:`htmlResponse()`, this method creates a PSR-7 Response
with the :html:`Content-Type: application/json` header and http code `200 Ok`.
If argument :php:`$json` is omitted, the current view is rendered automatically.

Example:

.. code-block:: php

   public function listApiAction(): ResponseInterface
   {
       $items = $this->itemRepository->findAll();
       $this->view->assign('value', [
            'items' => $items
        ]);

       return $this->jsonResponse();
   }

Above example is equivalent to:

.. code-block:: php

   public function listApiAction(): ResponseInterface
   {
       $items = $this->itemRepository->findAll();
       $this->view->assign('value', [
            'items' => $items
        ]);

       return $this->responseFactory
           ->createResponse()
           ->withHeader('Content-Type', 'application/json; charset=utf-8')
           ->withBody($this->streamFactory->createStream($this->view->render()));
   }

.. index:: PHP-API, NotScanned, ext:extbase
