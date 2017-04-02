.. include:: ../../Includes.txt

=============================================
Feature: #80154 - Retrieve session data in TS
=============================================

See :issue:`80154`

Description
===========

As the session API has been modified, it is no longer possible to access
session data from TypoScript by accessing the formerly public property of
the fe_user with:

.. code-block:: typoscript

   page.10 = TEXT
   page.10.data = TSFE:fe_user|sesData|myext|mydata


This is being replaced by a more direct way, which allows for the same functionality:

.. code-block:: typoscript

   page.10 = TEXT
   page.10.data = session:myext|mydata

.. index:: Frontend, TypoScript