.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _login-process:

The login process
^^^^^^^^^^^^^^^^^

After the form is submitted the TYPO3 CMS authentication services will
validate the login credentials. After this process felogin will handle
the rest. This means that the felogin plugin must be visible for the
user who has logged in.

Felogin will then check any redirect options and generate the
appropriate content.

