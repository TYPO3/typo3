.. include:: /Includes.rst.txt


============================================================
Important: #87516 - Remove core HTTP RequestHandlerInterface
============================================================

See :issue:`87516`

Description
===========

The internal interface :php:`\TYPO3\CMS\Core\Http\RequestHandlerInterface` has
been removed in favor of PSR-15 request handler and middleware interfaces which
are now used throughout the core.

.. index:: PHP-API, FullyScanned
