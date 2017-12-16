
.. include:: ../../Includes.txt

=========================================================================================================
Feature: #47919 - Possibility to configure an exception handler when rendering TypoScript content objects
=========================================================================================================

See :issue:`47919`

Description
===========

Exceptions which occur during rendering of content objects (typically plugins) will now be caught
by default in production context and an error message is shown as rendered output.
If this is done, the page will remain available while the section of the page that produces an error (throws an exception)
will show a configurable error message. By default this error message contains a random code which references
the exception which is also logged by the logging framework for developer reference.

Usage:

.. code-block:: typoscript

	# Use 1 for the default exception handler (enabled by default in production context)
	config.contentObjectExceptionHandler = 1

	# Use a class name for individual exception handlers
	config.contentObjectExceptionHandler = TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler

	# Customize the error message. A randomly generated code is replaced within the message if needed.
	config.contentObjectExceptionHandler.errorMessage = Oops an error occurred. Code: %s

	# Configure exception codes which will not be handled, but bubble up again (useful for temporary fatal errors)
	tt_content.login.20.exceptionHandler.ignoreCodes.10 = 1414512813

	# Disable the exception handling for an individual plugin/ content object
	tt_content.login.20.exceptionHandler = 0

	# ignoreCodes and errorMessage can be both configured globally …
	config.contentObjectExceptionHandler.errorMessage = Oops an error occurred. Code: %s
	config.contentObjectExceptionHandler.ignoreCodes.10 = 1414512813

	# … or locally for individual content objects
	tt_content.login.20.exceptionHandler.errorMessage = Oops an error occurred. Code: %s
	tt_content.login.20.exceptionHandler.ignoreCodes.10 = 1414512813

..

Impact
======

Instead of breaking the whole page when an exception occurs, an error message is shown for the part of the page that is broken.
Be aware that unlike before, it is now possible that a page with error message gets cached.
To get rid of the error message not only the actual error needs to be fixed, but the cache must be cleared for this page.
