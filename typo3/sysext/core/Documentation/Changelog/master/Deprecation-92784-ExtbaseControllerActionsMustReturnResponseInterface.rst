.. include:: ../../Includes.txt

==============================================================================
Deprecation: #92784 - Extbase controller actions must return ResponseInterface
==============================================================================

See :issue:`92784`

Description
===========

Until now, Extbase controller actions could return either nothing (void), null, a string, or an object that implements :php:`__toString()`.

From now on Extbase expects actions to return an instance of :php:`Psr\Http\Message\ResponseInterface` instead.


Impact
======

All actions that do not return an instance of :php:`Psr\Http\Message\ResponseInterface` trigger a deprecation log entry and will fail as of TYPO3 12.0.


Affected Installations
======================

All installations that use Extbase controller actions which don't return an instance of :php:`Psr\Http\Message\ResponseInterface`.


Migration
=========

Since the core follows not only PSR-7 (https://www.php-fig.org/psr/psr-7/) but also PSR-17 (https://www.php-fig.org/psr/psr-17/), which defines how response factories should look like, the core provides a factory to create various different response types. The response factory is available in all extbase controllers and can be used as a shorthand function to create responses for html and json, the two most used content types. The factory can also be used to create a blank response object whose content and headers can be set freely.

Example:

.. code-block:: php

   public function listAction(): ResponseInterface
   {
       $items = $this->itemRepository->findAll();
       $this->view->assign('items', $items);

       return $this->responseFactory->createHtmlResponse($this->view->render());
   }

This example only shows the most common use case. Returning html with a `Content-Type: text/html` sent with http code `200 Ok`.

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


Of course you are free to adjust the to be returned response.

Example:

.. code-block:: php

   public function listAction(): ResponseInterface
   {
       $items = $this->itemRepository->findAll();
       $this->view->assign('items', $items);

       return $this->responseFactory
           ->createHtmlResponse($this->view->render())
           ->withHeader('Cache-Control', 'must-revalidate')
           ->withStatus(200, 'Super ok!')
       ;
   }

.. tip::

   Since Extbase uses PSR-7 responses, you should make yourself familiar with its API.
   Documentation and more information regarding PSR-7 responses can be found here: https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface


.. index:: PHP-API, NotScanned, ext:extbase
