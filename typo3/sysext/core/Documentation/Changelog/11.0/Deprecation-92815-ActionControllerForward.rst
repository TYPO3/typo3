.. include:: /Includes.rst.txt

=================================================
Deprecation: #92815 - ActionController::forward()
=================================================

See :issue:`92815`

Description
===========

Method :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::forward()` has been marked as deprecated
in favor of returning a :php:`TYPO3\CMS\Extbase\Http\ForwardResponse` in a controller action.


Impact
======

Calling :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::forward()`,
which itself throws a `TYPO3\CMS\Extbase\Mvc\Exception\StopActionException` to initiate abortion
of the current request and to initiate a new request, will also trigger PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations using method :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::forward()`.


Migration
=========

Instead of calling the helper method, a controller action must return a :php:`TYPO3\CMS\Extbase\Http\ForwardResponse`.

Before:

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

After:

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
