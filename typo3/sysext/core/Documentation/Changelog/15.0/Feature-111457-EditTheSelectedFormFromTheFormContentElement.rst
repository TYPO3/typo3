..  include:: /Includes.rst.txt

..  _feature-111457-1794680900:

=======================================================================
Feature: #111457 - Edit the selected form from the form content element
=======================================================================

See :issue:`111457`

Description
===========

The form content element only allowed editors to select a form definition.
Changing that form meant leaving the record, opening the form module and
finding the form again.

A new button "Edit in form editor" below the form definition selector opens
the selected form in the form editor. Closing the form editor returns to the
content element: to the record when it was edited in full, and to the page
module when it was edited in the contextual edit sheet. Unsaved changes of the
record are confirmed before the form editor opens.

The button is shown for existing content elements to backend users who may
edit the selected form in the form editor. That requires access to the form
editor module, write access to the storage of the form and, for forms stored
in extension paths, `persistenceManager.allowSaveToExtensionPaths`. Selecting
another form saves and reloads the record, and the button opens the new form
afterwards.

Below the button, these backend users additionally see:

*   how many content elements use the form, if it is used more than once,
    since a change in the form editor affects all of them,
*   in a workspace, that forms are not versioned and changes in the form
    editor are live immediately.

Impact
======

Editors can change the form a content element uses directly from the content
element. Backend users without access to the form editor see the form content
element unchanged.

..  index:: Backend, FlexForm, ext:form
