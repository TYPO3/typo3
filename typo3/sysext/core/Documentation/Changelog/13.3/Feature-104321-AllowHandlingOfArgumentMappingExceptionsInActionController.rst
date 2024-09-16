.. include:: /Includes.rst.txt

.. _feature-104321-1720369379:

====================================================================================
Feature: #104321 - Allow handling of argument mapping exceptions in ActionController
====================================================================================

See :issue:`104321`

Description
===========

A new method :php:`handleArgumentMappingExceptions` has been introduced in
Extbase :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController` to improve
handling of exceptions that occur during argument mapping.

The new method supports optional handling of the following exceptions:

*   :php:`\TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException`,
    which occurs, when a given object UID can not be resolved to an existing record.
*   :php:`\TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException`,
    which occurs, when a required action argument is missing.

Handling of the exceptions can be enabled globally with the following TypoScript
configuration.

*  :typoscript:`config.tx_extbase.mvc.showPageNotFoundIfTargetNotFoundException = 1`
*  :typoscript:`config.tx_extbase.mvc.showPageNotFoundIfRequiredArgumentIsMissingException = 1`

The exception handling can also be configured on extension level with the
following TypoScript configuration.

*  :typoscript:`plugin.tx_yourextension.mvc.showPageNotFoundIfTargetNotFoundException = 1`
*  :typoscript:`plugin.tx_yourextension.mvc.showPageNotFoundIfRequiredArgumentIsMissingException = 1`
*  :typoscript:`plugin.tx_yourextension_plugin1.mvc.showPageNotFoundIfTargetNotFoundException = 1`
*  :typoscript:`plugin.tx_yourextension_plugin1.mvc.showPageNotFoundIfRequiredArgumentIsMissingException = 1`

By default, these options are set to `0`, which will lead to exceptions
being thrown (and would lead to errors, if not caught). This is the current
behavior of TYPO3.

When setting one of these values to `1`, the configured exceptions will not be thrown.
Instead, a :php:`pageNotFound` response is propagated, resulting in a 404 error being
shown.

Additionally, extension authors can extend or override the method
:php:`handleArgumentMappingExceptions` in relevant Controllers in order
to implement custom argument mapping exception handling.


Impact
======

Extension authors can now handle exceptions in implementations of a
:php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`,
which are thrown during argument mapping.

.. index:: Frontend, ext:extbase
