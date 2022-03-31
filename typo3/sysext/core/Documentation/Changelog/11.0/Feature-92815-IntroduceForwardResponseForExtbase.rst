.. include:: /Includes.rst.txt

=======================================================
Feature: #92815 - Introduce ForwardResponse for extbase
=======================================================

See :issue:`92815`

Description
===========

Since TYPO3 11.0, extbase controller actions can and should return PSR-7 compatible response objects.
To allow the initiation of forwarding to another controller action class :php:`TYPO3\CMS\Extbase\Http\ForwardResponse` has been introduced.

Minimal example:

.. code-block:: php

   <?php

   use Psr\Http\Message\ResponseInterface;
   use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
   use TYPO3\CMS\Extbase\Http\ForwardResponse;

   class FooController extends ActionController
   {
      public function listAction(): ResponseInterface
      {
           // do something

           return new ForwardResponse('show');
      }
   }


Example that shows the full api:

.. code-block:: php

   <?php

   use Psr\Http\Message\ResponseInterface;
   use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
   use TYPO3\CMS\Extbase\Http\ForwardResponse;

   class FooController extends ActionController
   {
      public function listAction(): ResponseInterface
      {
           // do something

           return (new ForwardResponse('show'))
               ->withControllerName('Bar')
               ->withExtensionName('Baz')
               ->withArguments(['foo' => 'bar'])
          ;
      }
   }

Impact
======

Class :php:`TYPO3\CMS\Extbase\Http\ForwardResponse` allows users to initiate forwarding to other controller actions with a PSR-7 compatible response object.

.. index:: PHP-API, ext:extbase
