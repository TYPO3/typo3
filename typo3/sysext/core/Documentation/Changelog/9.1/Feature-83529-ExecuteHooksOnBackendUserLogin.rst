.. include:: ../../Includes.txt

=====================================================
Feature: #83529 - Execute hooks on backend user login
=====================================================

See :issue:`83529`


Description
===========

When a user successfully logs in to the backend of TYPO3, registered hooks are executed.
Developers can register their hooks as shown below.

.. code-block:: php

   // Register hook on successful BE user login
   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['backendUserLogin'][] =
       \Vendor\MyExtension\Hooks\BackendUserLogin::class . '->dispatch';


On user login, method :php:`dispatch()` of class :php:`\Vendor\MyExtension\Hooks\BackendUserLogin`
is executed and the backend user array is passed as a parameter:

.. code-block:: php

    public function dispatch(array $backendUser)
    {
      if (isset($backendUser['user']['username'])) {
        $username = $backendUser['user']['username'];
        $email = $backendUser['user']['email'];
        // do something...
      }
    }


Impact
======

TYPO3 core developers as well as extension developers can develop functions which will be executed
when a backend user successfully logs in to the backend of TYPO3.
A typical use case would be any type of notification service.

.. index:: PHP-API, LocalConfiguration
