..  include:: /Includes.rst.txt


..  _quickstartEditors:

=======================
Quick Start for Editors
=======================

Are you an editor, the form extension has already been installed by your admin and you want
to get started quickly? Follow these steps:

..  rst-class:: bignums-xxl

1.  Create a new form

    Go to the ``Forms`` module and build a form using the form editor. With the
    form editor you can quickly build appealing forms.

2.  Insert the form on a page

    The next step is inserting the form on the desired page(s).

    #.  Open the page module in the backend.
    #.  Go to your desired page.
    #.  Create a new content element of type "Form". You can find this
        under the "Form Elements" tab.
    #.  Select your form on the "Plugin" tab.
    #.  Save the form content element.
    #.  Repeat steps 2 to 5 to insert the form on further pages.

View your form on your web site. Enjoy!

..  _quickstarteditors-edit-in-form-editor:

Changing the form from the content element
==========================================

..  versionadded:: 15.0
    :changelog: feature-111457-1794680900

    Before, you had to open the :guilabel:`Forms` module and find the form
    again to change it.

..  figure:: /Images/FormPluginEditInFormEditor.png
    :alt: Tab "Plugin" of the content element "Contact form", with the form
        definition "Contact (1)" selected and the button "Edit in form
        editor" below it

    The button below the form definition opens the selected form.

To change the form of a form content element, open the content element and
go to the tab :guilabel:`Plugin`. Click :guilabel:`Edit in form editor`
below the form definition. The form editor opens the selected form. When
you close the form editor, you return to the content element.

A change in the form editor affects every content element that uses the
form. If more than one content element uses it, a notice below the button
says how many.

In a workspace, a notice says that forms are not versioned. A change in the
form editor is visible on the website immediately.

If you do not see the button, you may not edit the form. Ask your
administrator for access to the form editor.
