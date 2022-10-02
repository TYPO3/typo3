.. include:: /Includes.rst.txt

.. _feature-83912:

=======================================================
Feature: #83912 - Specify Section in Redirect Finisher
=======================================================

See :issue:`83912`

Description
===========

It is now possible to specify a fragment in the Redirect finisher. This
allows a user to be redirected to a specific content element or relevant
section after completing a form.

Impact
======

A section can be defined in a form definition with the `fragment` option.
In the example below, :yaml:`fragment: '9'` refers to the content element
with `uid` 9. There is no need to add the :html:`#` character. It is also
possible to configure a custom section, e.g. :yaml:`fragment: 'foo'`.

..  code-block:: yaml

    finishers:
      -
        options:
          pageUid: '7'
          additionalParameters: ''
          fragment: '9'
        identifier: Redirect

.. index:: Backend, ext:form
