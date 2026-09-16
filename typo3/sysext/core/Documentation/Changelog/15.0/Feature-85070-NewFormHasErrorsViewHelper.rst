.. include:: /Includes.rst.txt

.. _feature-85070-1789587437:

=================================================
Feature: #85070 - New ViewHelper f:form.hasErrors
=================================================

See :issue:`85070`

Description
===========

A new condition ViewHelper :html:`<f:form.hasErrors>` tells whether a property
of the current form has validation errors:

..  code-block:: html

    <div class="{f:form.hasErrors(for: 'blog.title', then: 'has-error')}">
        <f:form.textfield property="title" />
    </div>

It extends the usual condition ViewHelper base class, so :html:`<f:then>` and
:html:`<f:else>` work as well:

..  code-block:: html

    <f:form.hasErrors for="blog.title">
        <f:then>The title has at least one validation error.</f:then>
        <f:else>The title is fine.</f:else>
    </f:form.hasErrors>

Validation results could already be read with
:html:`<f:form.validationResults>`, but only as a block: that ViewHelper
assigns a variable and renders its children, so it can not be used inline
inside an attribute. Setting a CSS class on a wrapping element therefore meant
wrapping the markup in a block and nesting an :html:`<f:if>` inside it.

The second reason is less obvious. :php:`\TYPO3\CMS\Extbase\Error\Result` offers
:php:`hasErrors()`, which also reports errors of sub properties, but that method
can not be reached from a template: Fluid resolves :html:`{results.errors}` to
:php:`getErrors()`, which only returns the errors of that very node. A template
checking :html:`{results.errors}` for :html:`for="author"` therefore sees
nothing when the error sits on :html:`author.email`. The new ViewHelper
evaluates :php:`hasErrors()` and reports errors of sub properties as well.

The :html:`for` argument is required and must be a non-empty property path. Any
other value raises an exception, so an unresolved or mistyped variable can not
silently match the whole form.

Impact
======

Templates can mark up the surroundings of a form field without wrapping them in
a block:

..  code-block:: html

    <div class="form-group {f:form.hasErrors(for: 'email', then: 'error')}">
        <label>Email</label>
        <f:form.textfield property="email" />
    </div>

For the error objects themselves, :html:`<f:form.validationResults>` remains the
ViewHelper to use. The error class of a single form field is still set by the
:html:`errorClass` argument of that field, which needs no extra ViewHelper.

.. index:: Fluid, ext:fluid
