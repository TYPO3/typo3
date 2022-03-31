.. include:: /Includes.rst.txt

=======================================================================================================================
Breaking: #92513 - Method signature change of TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface::processRequest
=======================================================================================================================

See :issue:`92513`

Description
===========

The signature of method :php:`TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface::processRequest`
changed in the regard that no longer :php:`$request` and :php:`$response` are passed into it.
Instead, only a :php:`$request` argument is needed. Additionally, that method now requires to return a response.


Impact
======

This change affects all classes that either implement said interface directly (presumably none)
and those classes (controllers) that override method :php:`processRequest()`
of class :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController`. Those, that override said method
will experience the following fatal error:

`Declaration of ... must be compatible with TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface::processRequest(TYPO3\CMS\Extbase\Mvc\RequestInterface $request): TYPO3\CMS\Extbase\Mvc\ResponseInterface`.


Affected Installations
======================

All installations that override method :php:`processRequest()` of class :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController`.


Migration
=========

There are two steps to migrate:

- Remove the now superfluous :php:`$response` argument
- Return a response object.

The latter is usually achieved by calling :php:`return parent::processRequest($request)` instead of just :php:`parent::processRequest($request)`.

.. index:: PHP-API, NotScanned, ext:extbase
