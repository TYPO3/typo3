
.. include:: ../../Includes.txt

=======================================================
Breaking: #52156 - Replaced JumpURL features with hooks
=======================================================

See :issue:`52156`

Description
===========

JumpURL handling
^^^^^^^^^^^^^^^^

The generation and handling of JumpURLs has been removed from the frontend extension and
has been moved to a new core extension called "jumpurl".

URL handler hooks
^^^^^^^^^^^^^^^^^

New hooks were introduced in :code:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`
and :code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` that allow
custom URL generation and handling.

This is how you can register a hook for manipulating URLs during link generation:

.. code-block:: php

	// Place this in your ext_localconf.php file
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers']['myext_myidentifier']['handler'] =
		\Company\MyExt\MyUrlHandler::class;

	// The class needs to implement the UrlHandlerInterface:
	class MyUrlHandler implements \TYPO3\CMS\Frontend\Http\UrlHandlerInterface {}

This is how you can handle URLs in a custom way:

.. code-block:: php

	// Place this in your ext_localconf.php file
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors']['myext_myidentifier']['processor']
		= \Company\MyExt\MyUrlProcessor::class;

	// The class needs to implement the UrlProcessorInterface:
	class MyUrlProcessor implements \TYPO3\CMS\Frontend\Http\UrlProcessorInterface {}


External URL page handling
^^^^^^^^^^^^^^^^^^^^^^^^^^

The core functionality for redirecting the user to an external URL when he hits a page with doktype "external"
is moved from the :code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` to the
:code:`\TYPO3\CMS\Frontend\Page\ExternalPageUrlHandler` class.


ResourceStorage adjustment
^^^^^^^^^^^^^^^^^^^^^^^^^^

The method :code:`\TYPO3\CMS\Core\Resource\ResourceStorage::dumpFileContents()` accepts an additional
parameter for overriding the mime type that is sent in the `Content-Type` header when delivering a file.

Impact
======

Unless the jumpurl extension is installed, no JumpURL related feature will work anymore.

If an extension tightly integrates into the JumpURL process it might break, because some of the related
methods have been removed, disabled or changed.

These methods have been removed and their functionality has been moved to the new jumpurl extension:

:code:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::locDataJU()`

:code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::locDataCheck()`

The :code:`$initP` parameter of the method  :code:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getMailTo()` has been removed.

The method :code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::setExternalJumpUrl()` has been marked as deprecated
and is an alias for the new :code:`initializeRedirectUrlHandlers()` method that does no jumpurl handling any more. The
new method only checks if the current page is a link to an external URL and sets the :code:`redirectUrl` property.

The method :code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::jumpUrl()` has also been marked as deprecated
and is an alias for the new :code:`redirectToExternalUrl()` method. The jumpurl handling has been removed from
this method. It loops over all registered URL handlers and handles the redirection to the :code:`redirectUrl`.


Affected installations
======================

All CMS 7.4 installations that use the JumpURL features or that use Extensions that rely on these features
or one of the removed methods.


Migration
=========

If you  want to use the JumpURL features you need to install the jumpurl extension. Your configuration should
work as before.

Please note that the configuration of the :ref:`filelink <t3tsref:filelink>` TypoScript function has changed.
Passing the :code:`jumpurl` parameter in the configuration has been marked as deprecated and will be removed in future versions.

You can now pass arbitrary configuration options for the typolink call that is used to generate
the file link in the :code:`typolinkConfiguration` parameter:

.. code-block:: typoscript

	lib.myfilelink = TEXT
	lib.myfilelink.value = fileadmin/myfile.txt
	lib.myfilelink.filelink {
		typolinkConfiguration.jumpurl = 1
		typolinkConfiguration.jumpurl.secure = 1
	}


.. index:: PHP-API, ext:jumpurl, TypoScript, Frontend