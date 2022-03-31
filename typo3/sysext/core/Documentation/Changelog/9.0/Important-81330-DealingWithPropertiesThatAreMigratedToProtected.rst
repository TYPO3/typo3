.. include:: /Includes.rst.txt

===============================================================
Feature: #81330 - Dealing with properties that become protected
===============================================================

See :issue:`81330`
See Feature-81330-TraitToMigratePublicAccessToProtectedByDeprecation.rst

Intro
=====

Still a lot of classes of the core have public properties that are also used from within extensions. To reach full
encapsulation of classes, the public access to properties needs to be removed. The property access shall be done by
public methods only.

Public properties are migrated to protected and setters and getters are provided as needed. During a phase of
deprecation entries into the deprecation log are triggered, when an extension accesses a previously public property.
The code still keeps working until the next major release, when the deprecation tags are removed from the code.

What options do you have to act, if such an entry is triggered by your extension?

Types of public properties
==========================

The remaining public properties can be classified into two types. The first type serves as public API to the internal
state. The second type has the character of fully internal functionality.

The first type are accessors to configure a class, to inject components, to access results, etc. When these properties
are migrated to protected, methods are provided accordingly, like getters, setters or injectors. That's the new API
to use.

The second type, properties that are of fully internal functionality, typically has never been called from outside of
the class. For this type no setters and getters are provided. If an extension is accessing this type, it's most likely
an ugly hack that is asking for clean solution.

Strategies to migrate extensions
================================

Using the public API of methods
-------------------------------

Refactor the extension to use the new API of public accessor methods to access the internal state.

Finding a better design
-----------------------

If you were accessing a property of the second type, the fully internal one, it's time to improve the design of
your extension. If you think the flaw of design is on side of the core, review the class. Provide your suggestions by
using the bug tracker or commit patches.

Claiming getters and setters
----------------------------

Your extension may provide a valid use case for a public accessor that nobody was thinking of. Adding getters and
setters is no big deal and we like to see your extension working. Please raise your hand early during the period of
deprecation. Nothing needs to break.

Using reflection
----------------

You could consider to force public access to the property by reflection. This is ugly and not recommended.
You could do this as a quick and dirty workaround, for example when you didn't act early enough.

The second case to use reflection is to write unit tests. In the ideal world there should be no reason to access
protected properties by unit tests. In the real world there are good reasons now and then to do so.

There will be no warning if protected properties are changed as they are internal. Be aware, that your extension or
your unit test may break suddenly, when you use this kind of workaround.

.. index:: PHP-API
