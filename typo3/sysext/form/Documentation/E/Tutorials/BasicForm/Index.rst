.. include:: /Includes.rst.txt


.. _editors-basicForm:

===========================
Create a basic contact form
===========================

In this tutorial, you will learn how to create a basic contact form.

Let's define what we need:

- contact information from our visitors: first name, last name, email address
- the message from our visitor: a text area where they can enter their message
- an email to us with their message
- a confirmation message after sending the form

All fields shall be required in this case.

Let's get started:

.. rst-class:: bignums-xxl

#. Create a new form

   Go to the ``Forms`` module and create a new form by clicking on "Create new form".

   .. figure:: Images/1_newForm.png
      :alt: The form module - click the button

      The form module without any forms - click the button to create one.

#. Choose a name

   Choose a name for your form - something you will recognize later on - and click "Next"

   .. figure:: Images/2_newForm_wizard1.png
      :alt: The form creation wizard - step 1

#. Click "Next"

   As we are creating a basic form, step 2 will be automatically done and we directly see step 3. Click "Next" again.

   .. figure:: Images/2_newForm_wizard2.png
      :alt: The form creation wizard - step 3

#. Create new element

   You will now see the form editor view for your new form. Click on "Create new element" to add a field to your form.

   .. figure:: Images/3_createElement_1.png
      :alt: Create New Element Button

#. Add "First Name"

   The first field we want to create is the "First Name". Choose "Text" from "Basic Elements" to create a simple text field for the first name.

   .. figure:: Images/3_createElement_2.png
      :alt: Create New Text Element

#. Set options for "First Name"

   After selecting the "Text" type, we get new options in the inspector panel:

   .. figure:: Images/3_createElement_3.png
      :alt: Fields for a simple text field

   Fields for a simple text field.

   1. Label: Enter a label for your field - in this case "First Name".
   2. Description: Enter a description - something that helps your visitors to know what they should enter.
   3. Placeholder: Enter an example value for the field - this will be used as placeholder in the frontend.
   4. Required Field: Activate the checkbox to make your field required.
   5. Enter an error message for users who forget filling the field.
   6. Add a validator "Non-XML text" to only allow simple text input.

#. Repeat

   Repeat the steps in 6 for the "Last Name" field.

   .. figure:: Images/4_lastName.png
      :alt: Fields for the "Last Name" text field.

#. Add Email address

   Similar to the previous two fields, add an email field. Choose type `email` to automatically get an email field. Set an error message if the validation fails.

   .. figure:: Images/5_email.png
      :alt: Fields for the email field.

#. Add textarea for message

   Choose the type `Textarea` for the message field.

   .. figure:: Images/6_textarea_1.png
      :alt: Choose textarea field.

   The "Textarea" type in the overview.

#. Add options for the message field

   Set label, description and error messages.

   .. figure:: Images/6_textarea_2.png
      :alt: Configure textarea field.

   Configure the message field.

#. Send an email on form submit

   When a user submits the form, we want to get an email. In form, that's what we call "a finisher" as it happens when "finishing" the form.

   .. figure:: Images/7_addFinisherEmail_1.png
      :alt: Adding a finisher

   Adding a finisher

   1. Click on the form name on the top left - here you can edit general form settings.
   2. Choose a finisher on the right - to send an email to yourself, choose "Email to receiver (you)".

#. Configure the email finisher

   You can configure the email finisher: Choose the subject, recipient, name and CC.

   .. figure:: Images/7_addFinisherEmail_2.png
      :alt: Configuring the finisher

   .. figure:: Images/7_addFinisherEmail_3.png
      :alt: Configuring the finisher - part two

   You can use fields from the form to pre-fill values via the `{+}` button. In our case, we want to configure the sender's name to use the first and last name from the form.

#. Save the form

   Click on "Save" to save the current state of the form - even if we aren't finished it's a good idea to save our state sometimes so we minimize the risk to lose data.

   .. figure:: Images/7_addFinisherEmail_4.png
      :alt: Saving the form

#. Add confirmation finisher

   After the user submitted the form we want to display a confirmation/ thank you message. To do that, choose the "Confirmation message" finisher.

   .. figure:: Images/8_addFinisherConfirmation_1.png
      :alt: Choose confirmation finisher

#. Add confirmation message

   Set the "Thank You" message in the options of the "Confirmation Finisher".

   .. figure:: Images/8_addFinisherConfirmation_2.png
      :alt: Set a confirmation message

#. Preview the form

   Your form is now fully configured and ready to be inserted on pages. Save it again and let's preview it.

   .. figure:: Images/85_preview.png
      :alt: Preview the form

   1. Click on the preview icon and see a rudimentary preview of your form. Notice the "Step" headline.

#. Remove the "Step" headline

   The "Step" headline does not make sense for our form, as we only have a single step and the headline should be configured on the page where we will insert the form later. To remove it, leave the preview and click on "Step" in the tree view on the left side. Empty the field with "Step" in it.

#. Save the form

   You can save the form and do another review - now it looks fine. Let's go and insert it on a page.

#. Choose a page for your form

   The form you configured can now be inserted on any page you want. Go to the page module and choose one.

   .. figure:: Images/9_selectPageForForm.png
      :alt: Select a page for your form

   1. Go to the page module.
   2. Choose a page in the page tree (for example: "Contact" for the Contact form :)).
   3. Click on `+ Content` to create a new content element.

#. Insert Plugin

   From the content element wizard, choose "Form" (in "Form elements" tab).

   .. figure:: Images/10_elementWizard.png
      :alt: Select a page for your form

#. Choose your form definition

   In the plugin tab, choose the form definition you just created. You can also use the "normal" TYPO3 fields like header to render a headline for your form.

   .. figure:: Images/10_chosenFormInCE.png
      :alt: Choose the form definition

   Having a separate form definition allows you to insert the form on many pages, customizing for example the headline in each case.

#. Save the content element and enjoy!

   Save the content element and go and view your web page. You can now see your finished form.

   .. figure:: Images/11_finishedForm.png
      :alt: The finished form

   Depending on your frontend, your form might look different.

   Congratulations, you did it! You created a fully functional contact form.

