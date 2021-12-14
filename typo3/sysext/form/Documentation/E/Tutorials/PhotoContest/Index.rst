
.. include:: /Includes.rst.txt


.. _editors-photoContest:

===========================
Create a photo contest form
===========================

In this tutorial, you will learn how to create a photo contest with the form framework.

Let's define what we need:

- contact information from our visitors: name and email address
- a photo upload for our visitors
- an email to us with the participation
- terms and conditions for our contest
- a thank you page after submitting the form

Let's get started:

.. rst-class:: bignums-xxl

#. Create a new form

   Go to the ``Forms`` module and create a new form by clicking on "Create new form".

   .. figure:: Images/1_createForm.png
      :alt: The form module - click the button

      The form module with one form - click the button to create a new one.

#. Choose a name

   Choose a name for your form - something you will recognize later on - and click "Next"

   .. figure:: Images/2_wizard_1.png
      :alt: The form creation wizard - step 1

#. Click "Next"

   As we are creating a basic form, step 2 will be automatically done and we directly see step 3. Click "Next" again.

   .. figure:: Images/2_wizard_2.png
      :alt: The form creation wizard - step 3

#. Create new element

   You will now see the form editor view for your new form. Click on "Create new element" to add a field to your form. The first field we want to create is the "Name". Choose "Text" from "Basic Elements" to create a simple text field for the first name.

   .. figure:: Images/3_createElement_1.png
      :alt: Create New Element Button

#. Set options for "Name"

   After selecting the "Text" type, we get new options in the inspector panel:

   .. figure:: Images/3_createElement_2.png
      :alt: Fields for a simple text field

      Fields for a simple text field.

   1. Label: Enter a label for your field - in this case "Name".
   2. Placeholder: Enter an example value for the field - this will be used as placeholder in the frontend.
   3. Add a validator "Non-XML text" to only allow simple text input.

#. Create image upload field

   Similar to the previous field, add an image upload field. Choose type `Image Upload`.

   .. figure:: Images/4_createImageUpload_1.png
      :alt: Create Image Upload

#. Configure image upload field

   Set options for the image upload - for example choose specific image formats or enter a max file size.

   .. figure:: Images/4_createImageUpload_2.png
      :alt: Configure Image Upload

      The options of the image upload.

#. Create "Terms and Conditions"

   To display terms and conditions for our contest, we want to add a simple static text to the form. Choose "Static Text" from the available element types.

   .. figure:: Images/5_createStaticText_1.png
      :alt: The static text type

      Choose "Static Text".

#. Enter "Terms and Conditions"

   Fill the text box with the terms and conditions for your contest.

   .. figure:: Images/5_createStaticText_2.png
      :alt: Set options for Static Text

      Set options for static text.

#. Change the headline and buttons

   We want to have a nice headline for the form and the next button should read "Summary". To do that, click on "Step" (1) in the form tree and set the fields (2,3) on the right. You don't need to change the previous label, as we are on the first page and there is no previous in this case.

   .. figure:: Images/6_setOptionsForStep.png
      :alt: Set options for step

#. Create a summary page

   We want to create a summary page where the user can confirm his or her data again. Click on "Create new step" on the left to create a new page/step in the form.

   .. figure:: Images/7_createSummary_1.png
      :alt: Create new step

      Create a new step in a form.

   .. figure:: Images/7_createSummary_2.png
      :alt: Create new step - choose summary

      Choose summary as type for your new step

   .. figure:: Images/7_createSummary_3.png
      :alt: Create new step - configure summary

      Configure the summary headline and button labels.

#. Preview the form

   Click on the preview button to preview the form.

   .. figure:: Images/8_preview.png
      :alt: Preview the form

      Preview the form.

   Oh no! We forgot to create the email field. Let's do that next.

#. Add an email field

   Go back to editing the form (1) and click on "Create new element" (2).

   .. figure:: Images/9_missingEmail_1.png
      :alt: Switch to editing and Create new element

      Switch to editing and create new element

   .. figure:: Images/9_missingEmail_2.png
      :alt: Choose "email address"

      The "email address" type

   .. figure:: Images/9_missingEmail_3.png
      :alt: Configure the email address field

      Configure the email address field.

   .. figure:: Images/9_missingEmail_4.png
      :alt: Move the email address field

      Move the email address field to a better position via drag and drop.

#. Save the form

   Your form is now fully configured and ready to be inserted on pages.

   .. figure:: Images/10_saveTheForm.png
      :alt: Save the form

      Save the form.

   You can save the form and do another review - now it looks fine. Let's go and insert it on a page.

#. Choose a page for your form

   The form you configured can now be inserted on any page you want. Go to the page module and choose one.

   .. figure:: Images/11_selectPage.png
      :alt: Select a page for your form

   1. Go to the page module.
   2. Choose a page in the page tree (for example: "Contest").
   3. Click on `+ Content` to create a new content element.

#. Insert Plugin

   From the content element wizard, choose "Form" (in "Form elements" tab).

   .. figure:: Images/12_chooseForm.png
      :alt: Choose form as type

#. Choose your form definition

   In the plugin tab, choose the form definition you just created. You can also use the "normal" TYPO3 fields like header to render a headline for your form.

   .. figure:: Images/13_chooseFormDefinition.png
      :alt: Choose the form definition

   Having a separate form definition allows you to insert the form on many pages, customizing for example the headline in each case.

#. Save the content element

   Save the content element and go and view your web page. You can now see your finished form.

   .. figure:: Images/14_frontend_1.png
      :alt: The finished form - Step 1

      Depending on your frontend, your form might look different.

   .. figure:: Images/14_frontend_2.png
      :alt: The finished form - Step 2

      Depending on your frontend, your summary page might look different.

   When testing your form, you might notice that it doesn't do anything yet when we fill it. That's bad. Let's change that.

#. Add email finisher

   Everytime someone fills the form we want to receive an email with the contest picture. Let's add an email finisher for that:

   .. figure:: Images/15_addEmail.png
      :alt: Add email finisher

      Configure the email finisher

#. Add redirect to "Thank You" page

   After submitting the form we want to redirect the user to a thank you page. There's a ready-made finisher for that, too - the "Redirect to a page" finisher:

   .. figure:: Images/16_addRedirect_1.png
      :alt: Redirect Finisher Options

      Redirect finisher with options.

   Choose "Redirect to a page" from the finisher menu. Click on the "Page" button to open the page browser.

   .. figure:: Images/16_addRedirect_2.png
      :alt: Page browser of redirect finisher.

      Page browser.

   Choose your thank you page.

   .. attention::

      Make sure that the redirect finisher is the last finisher - after the redirect no other finishers will be executed.

#. Test again - Enjoy!

   Save the form and reload the frontend. Now you can test the form again. After submitting you will now be redirected to the thank you page.

   .. figure:: Images/17_thankYouPage.png
      :alt: Thank you page.

      Depending on your frontend, your page might look different.
