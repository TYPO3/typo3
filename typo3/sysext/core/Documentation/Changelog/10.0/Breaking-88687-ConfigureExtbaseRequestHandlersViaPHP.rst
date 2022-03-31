.. include:: /Includes.rst.txt

=============================================================
Breaking: #88687 - Configure extbase request handlers via PHP
=============================================================

See :issue:`88687`

Description
===========

The configuration of extbase request handlers is no longer possible via typoscript.
All typoscript concerning the configuration of request handlers needs to be converted to php, residing
in :file:`EXT:Configuration/Extbase/RequestHandlers.php`.


Impact
======

Unless converted to php, the configuration in typoscript does no longer have any effect and therefore the registration
of request handlers will no longer work.


Affected Installations
======================

All installations that configure request handlers via typoscript.


Migration
=========

Every extension that used typoscript for such configuration must provide a php configuration class called:
:file:`EXT:Configuration/Extbase/RequestHandlers.php`

The migration is best described by an example:

.. code-block:: typoscript

   config.tx_extbase {
       mvc {
           requestHandlers {
               Vendor\Extension\Mvc\Web\FrontendRequestHandler = Vendor\Extension\Mvc\Web\FrontendRequestHandler
           }
       }
   }

This configuration will look like this, defined in php:

.. code-block:: php

   <?php
   declare(strict_types = 1);

   return [
       \Vendor\Extension\Mvc\Web\FrontendRequestHandler::class,
   ];

.. warning::

   With typoscript it was possible to override request handlers, registered by extensions loaded before the current one.
   This also included core extensions. This approach has been bad practice because suitable request handlers are chosen
   by their ability to handle a request and their priority. The evaluation of priorities could have been bypassed by
   overriding keys of the configuration. This is no longer possible as request handler configuration files can only
   add possible request handlers. Hence the omitted keys in the configuration array.

.. index:: TypoScript, NotScanned, ext:extbase
