.. include:: ../../Includes.txt

======================================================
Deprecation: #94209 - Backend ModuleLayout ViewHelpers
======================================================

See :issue:`94209`

Description
===========

The following fluid view helpers have been deprecated:

* :php:`be:moduleLayout`
* :php:`be:moduleLayout.menu`
* :php:`be:moduleLayout.menuItem`
* :php:`be:moduleLayout.button.linkButton`
* :php:`be:moduleLayout.button.shortcutButton`

These view helpers partially mimic their counterparts of the PHP based
:php:`ModuleTemplate` API. They found some usage in backend modules
when the 'doc header' handling was done in fluid.

The view helpers however relied on knowledge that shouldn't be the scope
of a view component to be useful, especially variables like current action
and controller had to be assigned to the view in many cases.

Additionally, those view helpers were only a sub set of the ModuleTemplate
functionality and created a second API for the same problem domain and
various scenarios like good shortcut implementation and main drop down
state were hard to solve when using these view helpers.


Impact
======

Using these view helpers will log a deprecation message, they will be
removed with v12.


Affected Installations
======================

Some extensions with backend modules may use these view helpers. Searching
templates for string :php:`be:moduleLayout` should reveal usages. Extensions
extending the PHP classes are found by the extension scanner as a weak match.


Migration
=========

In general, extensions using these view helpers should switch to using the
PHP API based on class :php:`ModuleTemplate`, usually initialized by class
:php:`ModuleTemplateFactory` instead. All core extensions that render backend
modules provide lots of usage examples and the fluent API is quite straight
forward.

For extbase base backend modules, the basic idea is that the 'doc header'
should be handled within controller actions, while the module body is rendered
by the fluid view component.

In case an extension heavily relies on the deprecated view helpers and the
functionality should be kept with as little work as possible, the easiest
way is of course to simply copy the according view helpers to the extension
directly and to just adapt the namespace in templates accordingly.


.. index:: Backend, Fluid, PartiallyScanned, ext:backend
