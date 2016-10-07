
.. include:: ../../Includes.txt

===================================================
Feature: #54887 - Post-processing of the previewUrl
===================================================

See :issue:`54887`

Description
===========

An additional hook has been added to the method `BackendUtility::viewOnClick()` to
post-process the preview url.

The hook is called with the following signature:

.. code-block:: php

   /**
    * @param string $previewUrl
    * @param int $pageUid
    * @param array $rootLine
    * @param string $anchorSection
    * @param string $viewScript
    * @param string $additionalGetVars
    * @param bool $switchFocus
    * @return string The processed preview URL
    */
   function postProcess($previewUrl, $pageUid, $rootLine, $anchorSection, $viewScript, $additionalGetVars, $switchFocus)


Register the hook
-----------------

Register a hook class which implements the method with the name `postProcess`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass'][] = \VENDOR\MyExt\Hooks\BackendUtilityHook::class;

.. index:: Backend, PHP-API
