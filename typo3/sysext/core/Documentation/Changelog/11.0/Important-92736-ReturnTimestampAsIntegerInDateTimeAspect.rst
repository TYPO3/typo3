.. include:: /Includes.rst.txt

=================================================================
Important: #92736 - Return timestamp as integer in DateTimeAspect
=================================================================

See :issue:`92736`

Description
===========

The :php:`DateTimeAspect`, introduced to supersede the use of superglobals like
:php:`$GLOBALS['EXEC_TIME'],` can be used to retrieve the current timestamp.

.. code-block:: php

   $context = GeneralUtility::makeInstance(Context::class);

   // Used instead of $GLOBALS['EXEC_TIME']
   $currentTimestamp = $context->getPropertyFromAspect('date', 'timestamp');

This timestamp is now correctly returned as :php:`int` instead of :php:`string`.

Therefore, extension authors should check if they currently rely on receiving
the timestamp as :php:`string` and if so, adjust the consuming code accordingly.

.. index:: PHP-API, ext:core
