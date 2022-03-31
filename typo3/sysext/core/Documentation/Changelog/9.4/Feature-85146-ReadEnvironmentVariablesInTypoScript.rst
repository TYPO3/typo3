.. include:: /Includes.rst.txt

==========================================================
Feature: #85146 - Read environment variables in TypoScript
==========================================================

See :issue:`85146`

Description
===========

There is a new TypoScript value modifier `getEnv()`. The modifier checks if the variable given as its argument is set
and reads the value if so, overriding any existing value. If the environment variable is not set, the variable given on
the left-hand side of the expression is not changed.


Impact
======

Write a TypoScript statement like this to use environment values in your TypoScript:

.. code-block:: typoscript

    # Define default value
    myConstant = defaultValue
    # Enable overriding by environment variable
    myConstant := getEnv(TS_MYCONSTANT)

To have a value actually inserted, your PHP execution environment (webserver, PHP-FPM) needs to have these variables
set, or you need a mechanism like dotenv to set them in your running TYPO3.

As it is a syntax feature you can use it in both constants and setup plus it gets cached, as opposed to the getText
`getenv` feature.

.. index:: TypoScript, ext:core
