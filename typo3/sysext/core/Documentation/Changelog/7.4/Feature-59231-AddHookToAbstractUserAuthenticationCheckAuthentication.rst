
.. include:: ../../Includes.txt

============================================================================
Feature: #59231 - Hook for AbstractUserAuthentication::checkAuthentication()
============================================================================

See :issue:`59231`

Description
===========

Hook to post-process login failures in `AbstractUserAuthentication::checkAuthentication`.
By default the process sleeps for five seconds in case of failing. By using this hook, different solutions for
brute force protection can be implemented.

Register like this:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing'][] = 'My\\Package\\HookClass->hookMethod';
