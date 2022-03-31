.. include:: /Includes.rst.txt

=====================================================
Deprecation: #94351 - ext:extbase StopActionException
=====================================================

See :issue:`94351`

Description
===========

To further prepare towards clean PSR-7 request / response handling in
Extbase, the Extbase internal exception
:php:`TYPO3\CMS\Extbase\Mvc\Exception\StopActionException`
has been deprecated.


Impact
======

No deprecation is logged, but the :php:`StopActionException` will be
removed in v12 as breaking change. Extension developers with Extbase
based controllers can prepare in v11 towards this.


Affected Installations
======================

Extensions with Extbase controllers that throw :php:`StopActionException` or
use methods :php:`redirect` or :php:`redirectToUri` from Extbase
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`
are affected.


Migration
=========

As a goal, Extbase actions will *always* return a
:php:`\Psr\Http\Message\ResponseInterface`
in v12. v11 prepares towards this, but still throws the :php:`StopActionException`
in :php:`redirectToUri`. Developers should prepare towards this.

Example before:

.. code-block:: php

   public function fooAction()
   {
      $this->redirect('otherAction');
   }

Example compatible with v10, v11 and v12 - IDE's and static code analyzers
may complain in v10 and v11, though:

.. code-block:: php

   public function fooAction(): ResponseInterface
   {
      // A return is added!
      return $this->redirect('otherAction');
   }

.. index:: PHP-API, NotScanned, ext:extbase
