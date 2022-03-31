.. include:: /Includes.rst.txt

=====================================================================
Feature: #93455 - Backend Routes restricted to specified HTTP methods
=====================================================================

See :issue:`93455`

Description
===========

Individual Backend Routes in TYPO3 Backend can now be configured
to only apply for specific HTTP methods (e.g. GET or POST).

This way, custom Backend routes can be limited to only allow
submitted form content to be delivered via HTTP POST for example.

The underlying symfony routing component, which is already used
in TYPO3 Backend for routing through the proper API, is handling the
restriction to the HTTP method / verb automatically.


Impact
======

Any Backend route, configured in extensions via
:file:`EXT:my_extension/Configuration/Backend/Routes.php`
and :file:`EXT:my_extension/Configuration/Backend/AjaxRoutes.php`
has a new, optional property :php:`methods`, which expects an array
to set one or more HTTP verbs, such as :html:`GET`, :html:`POST`, :html:`PUT` or :html:`DELETE`.

If no property is given, no restriction to a HTTP method is set.

Example:

.. code-block:: php

   return [
      'my_route' => [
         'path' => '/benni/my-route',
         'methods' => ['POST'],
         'target' => MyVendor\MyPackage\Controller\MyRouteController::class . '::submitAction'
      ]
   ];

.. index:: Backend, PHP-API, ext:backend
