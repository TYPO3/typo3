=========================================================
Feature: #61351 - Add data attribute to Fluid ViewHelpers
=========================================================

Description
===========

Since HTML5 Elements can contain a generic data attribute,
Fluid provides for those elements the possibility to add
key-value pairs as array, which will be rendered as
`data-$key="$value"`.

:code: <f:form.textfield data="{foo: 'bar', baz: 'foos'}" />

Impact
======

Generic data attributes do not need to be passed by the
`additionalAttributes` array anymore making the viewhelper
more straight forward to use.
