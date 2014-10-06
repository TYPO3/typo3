.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _writing-extensions-introduction:

Introduction
^^^^^^^^^^^^

If you want your TYPO3 extensions to be DBAL compliant you might have
to rewrite parts of them. The most basic DBAL support is to substitute
all direct ``mysql\*()`` function calls with the wrapper functions found
in ``DatabaseConnection`` accessed through the global object ``$GLOBALS['TYPO3_DB']``.
The most radical support is to consistently use the methods in the
``DatabaseConnection`` class prefixed ``exec_`` - they will automatically create the
proper SQL behind the scenes and execute the queries right away,
returning a result pointer/object. This allows the DBAL to handle an
ultimate amount of the interaction with the database for you.
