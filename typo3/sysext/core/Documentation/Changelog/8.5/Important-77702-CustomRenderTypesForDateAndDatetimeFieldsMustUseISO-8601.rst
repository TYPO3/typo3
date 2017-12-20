.. include:: ../../Includes.txt

======================================================================================
Important: #77702 - Custom render types for date and datetime fields must use ISO-8601
======================================================================================

See :issue:`77702`

Description
===========

Historically, TYPO3 used its own special, localized formats for passing date and
datetime values between server and client. To get rid of any possible problems with
that, we now use ISO-8601, a standard format for date/time representations.

Due to that, you need to adapt your **custom FormEngine render types** if you use
them for any date/datetime fields, even those stored as integers in the database
(eval=date/datetime).

.. index:: Backend, Database, TCA