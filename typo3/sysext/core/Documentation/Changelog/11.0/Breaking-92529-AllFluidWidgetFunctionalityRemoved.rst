.. include:: /Includes.rst.txt

=========================================================
Breaking: #92529 - All Fluid widget functionality removed
=========================================================

See :issue:`92529`

Description
===========

First things first: All fluid widgets and all widget functionality have been removed!

The most important issue of fluid widgets is, that they initiate sub requests to a
controller from inside the view and output their content. Those sub requests bring
another layer of complexity to the TYPO3 world that is impossible to handle properly
as many bug reports show. TYPO3 already uses some kind of namespacing for url query
arguments to separate arguments for different plugins on a single page. Widgets
introduced another layer which made it necessary to yet again introduce namespacing
in the existing namespace.

To make fluid widgets work, they need to work on the request object of the parent
plugin, i.e. the one that renders the view which holds the widget. This is a problem
regarding our efforts using PSR-7 request objects in Extbase which are immutable
by definition.

A special kind of widget is the ajax widget which introduced even more complexity.
One example was the already removed auto complete widget. The widget could be used
to fetch values from the database for autocompletion while entering a textfield in
a form. In order to perform that kind of magic, fluid came with a new page type (7076)
for handling incoming ajax requests. Since that endpoint didn't know about the specifics
of the widget that should be rendered, the widget context had to be serialized before
the ajax request, bound to the user with a unique id and stored in the users session
data just to be unserialized moments later to have a back reference to initiating request.

The fluid widgets violated the design pattern "separation of concern" to a degree
that they caused more trouble than benefit. Therefore, fluid widgets have been
removed from TYPO3 core.

Impact
======

- All fluid templates that used an existing widget will no longer work as expected.
- Also, all custom widgets of users will no longer work and have to be replaced
  with custom solutions.


Affected Installations
======================

All installations that either used widgets defined by the core or those installations
that created own widgets.


Migration
=========

There is no simple migration strategy for all widgets but the most common
functionality (pagination) can be solved with a new pagination core api. The main
difference compared to a widget is that the pagination has to be initialized
in the controller action and not in the view.

For all other widgets, custom solutions have to be found.

.. index:: Fluid, PHP-API, NotScanned, ext:fluid
