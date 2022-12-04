.. include:: /Includes.rst.txt

.. _important-98502-1664738430:

=============================================================
Important: #98502 - Correct fallback to default error handler
=============================================================

See :issue:`98502`

Description
===========

The site configuration allows to define the HTTP error status code to be
handled, e.g. by showing the content of a given page.

The backend module states "Make sure to have at least 0 (not defined otherwise)
configured in order to serve helpful error messages to your visitors." but the
fallback to "0" has not been implemented yet until now.

If no error handling for the given HTTP error status code is configured, but
one for "any error not defined otherwise", the latter is used as fallback. This
reduces the configuration effort if only one error handling configuration is
used for all kind of error codes.

.. index:: Frontend, ext:core
