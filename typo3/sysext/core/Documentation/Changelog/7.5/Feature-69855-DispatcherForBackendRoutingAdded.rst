
.. include:: ../../Includes.txt

======================================================
Feature: #69855 - Dispatcher for Backend Routing added
======================================================

See :issue:`69855`

Description
===========

The previously introduced Backend Routing is updated so that Routes must be
defined with a class name and method name, or a Closure / callable. The
controller/action or closure is now named as "target".

Example from `EXT:backend/Configuration/Backend/Routes.php`

.. code-block:: php

	// Logout script for the TYPO3 Backend
	'logout' => [
		'path' => '/logout',
		'target' => Controller\LogoutController::class . '::logoutAction'
	]


Impact
======

Each method that is registered will receive both the Request object and the
Response object which can be manipulated for output.

The fixed `ControllerInterface` is not needed anymore and will be removed.


.. index:: PHP-API, Backend
