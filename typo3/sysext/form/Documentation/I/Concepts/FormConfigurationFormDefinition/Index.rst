.. include:: /Includes.rst.txt


.. _concepts-formdefinition-vs-formconfiguration:

Form configuration vs. form definition
======================================

So far, we have only described the configuration of the form framework.
Once again, based on prototypes, the **form configuration** allows you to
define:

- which form elements, finishers, and validators are available,
- how those objects are pre-configured,
- how those objects will be displayed within the frontend and backend.

In contrast, the **form definition** describes the specific form, including

- all form elements and their corresponding validators,
- the order of the form elements within the form, and
- the finishers which are fired as soon as the form has been submitted.
- Furthermore, it defines the concrete values of each property of the
  mentioned aspects.

In other words, the **prototype configuration** defines the existence of a
form element of type ``Text`` globally. The **form definition** declares
that such a form element of type ``Text`` is located on page 1 at position
1 of a specific form. In addition, it carries the information that this form
element comes with the HTML attribute "placeholder" with value "Your name
here". The form definition is written by the ``form editor``.


Example form definition
-----------------------

.. code-block:: yaml

   identifier: ext-form-simple-contact-form-example
   label: 'Simple Contact Form'
   prototype: standard
   type: Form

   finishers:
     -
       identifier: EmailToReceiver
       options:
         subject: 'Your message'
         recipients:
           your.company@example.com: 'Your Company name'
           ceo@example.com: 'CEO'
         senderAddress: '{email}'
         senderName: '{name}'

   renderables:
     -
       identifier: page-1
       label: 'Contact Form'
       type: Page

       renderables:
         -
           identifier: name
           label: 'Name'
           type: Text
           properties:
             fluidAdditionalAttributes:
               placeholder: 'Name'
           defaultValue: ''
           validators:
             -
               identifier: NotEmpty
         -
           identifier: subject
           label: 'Subject'
           type: Text
           properties:
             fluidAdditionalAttributes:
               placeholder: 'Subject'
           defaultValue: ''
           validators:
             -
               identifier: NotEmpty
         -
           identifier: email
           label: 'Email'
           type: Text
           properties:
             fluidAdditionalAttributes:
               placeholder: 'Email address'
           defaultValue: ''
           validators:
             -
               identifier: NotEmpty
             -
               identifier: EmailAddress
         -
           identifier: message
           label: 'Message'
           type: Textarea
           properties:
             fluidAdditionalAttributes:
               placeholder: ''
           defaultValue: ''
           validators:
             -
               identifier: NotEmpty
         -
           identifier: hidden
           label: 'Hidden Field'
           type: Hidden
     -
       identifier: summarypage
       label: 'Summary page'
       type: SummaryPage
