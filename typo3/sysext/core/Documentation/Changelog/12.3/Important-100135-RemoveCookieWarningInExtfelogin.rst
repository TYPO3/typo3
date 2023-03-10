.. include:: /Includes.rst.txt

.. _important-100135-1678453394:

=========================================================
Important: #100135 - Remove cookie warning in ext:felogin
=========================================================

See :issue:`100135`

Description
===========

The cookie warning message in ext:felogin is never shown, since it depends on
conditions, which will never be met. The cookie warning message can also be
considered superfluous, since a similar message is already shown, if
authentication was not successful.

Code affecting the non-working cookie warning message has therefore been
removed from ext:felogin. TYPO3 users should remove code from custom templates,
which depend on the `{cookieWarning}` variable.

.. index:: ext:felogin
