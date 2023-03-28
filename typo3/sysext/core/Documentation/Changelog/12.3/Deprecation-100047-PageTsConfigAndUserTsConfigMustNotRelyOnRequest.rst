.. include:: /Includes.rst.txt

.. _deprecation-100047-1677608959:

===============================================================================
Deprecation: #100047 - Page TSconfig and user TSconfig must not rely on request
===============================================================================

See :issue:`100047`

Description
===========

Using :typoscript:`request` and function :typoscript:`ip()` in page TSconfig or
user TSconfig conditions has been marked as deprecated in TYPO3 v12. Such conditions
will stop working in TYPO3 v13 and will always evaluate to false.

Page TSconfig and user TSconfig should not rely on request related data: They should
not check for given arguments or similar: the main reason is that the Backend
:php:`DataHandler` makes heavy use of page TSconfig, but the DataHandler itself is
not request-aware. The DataHandler (the code logic that updates data in the database
in the backend) can be used and must work in a CLI context, so any page TSconfig that
depends on a given request is flawed by design since it will never act as expected
in a CLI context.

To avoid further issues with the DataHandler in web and CLI contexts,
TSconfig-related conditions must no longer be request-aware.


Impact
======

Using request-related conditions in page TSconfig or user TSconfig will raise a
deprecation level warning in TYPO3 v12 and will always evaluate to false in
TYPO3 v13.


Affected installations
======================

There may be instances of page TSconfig using conditions using
request-related conditions. These need to look for different solutions
that achieve a similar goal.


Migration
=========

Try to get rid of :typoscript:`ip()` or request related information in
page TSconfig conditions.

A typical example is highlighting something when a developer is
using the live domain:

..  code-block:: typoscript

    [request.getRequestHost() == 'development.my.site']
        mod.foo = bar
    [end]

Switch to the application context in such cases:

..  code-block:: typoscript

    [applicationContext == "Development"]
        mod.foo = bar
    [end]

There are similar alternatives for other use cases: You can not rely on given
GET / POST arguments anymore, but it should be possible to switch to
:typoscript:`backend.user.isAdmin` or similar conditions in most cases, or to
handle related switches within controller classes in PHP.

Relying on request arguments for page TSconfig conditions is fiddly,
especially when using this for core related controllers: those are not considered
API and may change at anytime. Instead, needs should be dealt with explicitly using
toggles within controllers.


.. index:: TSConfig, NotScanned, ext:backend
