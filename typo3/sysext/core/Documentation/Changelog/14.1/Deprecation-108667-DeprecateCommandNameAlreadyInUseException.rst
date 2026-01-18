..  include:: /Includes.rst.txt

..  _deprecation-108667-1768743166:

=================================================================
Deprecation: #108667 - Deprecate CommandNameAlreadyInUseException
=================================================================

See :issue:`108667`

Description
===========

The exception :php:`TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException`
is unused within TYPO3 Core and has been deprecated.


Impact
======

Creating a new instance of :php:`TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException`
will trigger a PHP deprecation message.


Affected installations
======================

TYPO3 installations with custom extensions using this exception.


Migration
=========

As the exception is unused in TYPO3 Core, there is no direct replacement.
Extensions relying on this exception should implement their own exception
if needed.

..  index:: PHP-API, FullyScanned, ext:core
