.. include:: /Includes.rst.txt

.. _feature-91354:

===========================================================
Feature: #91354 - Integrate server response security checks
===========================================================

See :issue:`91354`

Description
===========

In order to evaluate potential server misconfigurations and to reduce
the potential of security implications in general, a new HTTP response
check is integrated to "Environment Status" and the "Security" section
in the reports module.


Impact
======

It is evaluated whether non-standard file extensions lead to unexpected
handling on the server-side, such as `test.php.wrong` being evaluated
as PHP or `test.html.wrong` being served with `text/html` content type.

Besides that, HTTP host header injection is evaluated. In case `HTTP_HOST` or
`SERVER_NAME` were reported to contain unexpected values, this is an indicator
for being affected by this configuration flaw. For Apache web servers, using the
configuration directive `UseCanonicalName On` might solve this problem.

Details are explained in `TYPO3 Security Guidelines for Administrators`_.

.. _TYPO3 Security Guidelines for Administrators: https://docs.typo3.org/m/typo3/reference-coreapi/10.4/en-us/Security/GuidelinesAdministrators/Index.html#file-extension-handling

.. index:: ext:install
