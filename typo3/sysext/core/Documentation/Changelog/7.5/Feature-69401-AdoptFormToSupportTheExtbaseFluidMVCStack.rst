
.. include:: /Includes.rst.txt

========================================================================
Feature: #69401 - Adopt ext:form to support the Extbase/ Fluid MVC stack
========================================================================

See :issue:`69401`

Description
===========

Short summery
-------------

The form extension - including the custom data model, controller logic,
property validation, views and templating - has been adopted to support
the Extbase/ Fluid MVC stack. This allows better customization and
control of the generated behavior and markup by simply modifying Fluid
templates or utilizing own custom view helper logic. At the same time
the rewrite must not break current setups, i.e. the frontend rendering
must be as compatible as possible.

Details
-------

Fluid Rendering
^^^^^^^^^^^^^^^

The rendering of the frontend output is based on Fluid. Form relies on
the native Fluid viewhelpers of the core and ships 2 new viewhelpers
for optimal rendering of the SELECT object including the support of
OPTGROUP objects. Furthermore a viewhelper is included to optimize the
output of text mails.

To support existing setups a compatibility mode is introduced. The mode
is activated by default. This has no impact on the rendering as long as
no "old" TypoScript settings (like .layout =) are present. If old
rendering settings are used a compatibility theme is loaded which
guarantees maximum backwards compatibility including all the different
wrap-abilities like `containerWrap` and `elementWrap`.

For new installations it is recommended to switch off the compatibility
mode and use own Fluid templates to customize the output.

For each form object and view a Fluid partial is available. There are 3
views: the form itself (show), the confirmation page (confirmation) and
the email (postProcessor/ mail). The patch allows to customize the
frontend output for every single view, e.g. one can have a custom Fluid
partial for the BUTTON object of the confirmation view.

The `partialRootPath` can be overridden or extended to customize the form
objects on a global scope. Furthermore it is possible to set a partial
path for each form element on a local scope.

.. code-block:: typoscript

	10 = BUTTON
	10 {
		label = My button
		name = myButton
		partialPath = FlatElements/MyButton
	}

The Fluid rendering would look for a MyButton.html located in the
defined `partialRootPath`.

In addition it is now possible to decide if an element should be
rendered for a specific view. The visualisation can be adopted by using
the TypoScript settings `visibleInShowAction`, `visibleInConfirmationAction`
and `visibleInMail`. As an example, this is utilized to hide the FIELDSET
object on the confirmation page which was the default behaviour in
earlier versions of form.

Extensibility
^^^^^^^^^^^^^

It is now possible to register custom form objects and attributes
easily only by using TypoScript and Fluid. Form attributes can now be
cObjects and use stdWrap. This is only possible if the form was not
designed within the form wizard.

Furthermore 2 new signal slots are implemented to allow the
manipulation of the form objects and the submitted data.

Validation
^^^^^^^^^^

The validators are now using the extbase property mapping validation
process.

Additional information
^^^^^^^^^^^^^^^^^^^^^^

The session handling was dropped since it was unstable (see #58765). Now
form relies on the concepts of Extbase.

The unit tests have been adopted to reflect the code changes.

Future
^^^^^^

Further patches are needed to adopt the form wizard. The wizard still
works as it used to after applying this patch but it is not able to
reflect the new features like choosing a partial path for a single
element.

Another patch will take care of the documentation.

A few more patches will come which will fix some issues regarding the
validators and filters.


.. index:: ext:form, Fluid, ext:extbase, TypoScript
