.. include:: ../../Includes.txt

===============================================================
Deprecation: #92815 - ActionController::forward() is deprecated
===============================================================

See :issue:`92815`

Description
===========

Method :php:`TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController::forward()` is deprecated in favor of returning a :php:`TYPO3\\CMS\\Extbase\\Http\\ForwardResponse` in a controller action instead.


Impact
======

Calling :php:`TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController::forward()`, which itself throws a `TYPO3\\CMS\\Extbase\\Mvc\\Exception\\StopActionException` to initiate the abortion of the current request and to initiate a new request, will trigger a deprecation warning.


Affected Installations
======================

All installations that make use of the helper method :php:`TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController::forward()`.


Migration
=========

Instead of calling this helper method, a controller action must return a :php:`TYPO3\\CMS\\Extbase\\Http\\ForwardResponse`.

Example:

.. code-block:: php

   <?php

   use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

   class FooController extends ActionController
   {
      public function listAction()
      {
           // do something

           $this->forward('show');
      }

      // more actions here
   }


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

      // more actions here
   }


.. index:: PHP-API, NotScanned, ext:extbase
