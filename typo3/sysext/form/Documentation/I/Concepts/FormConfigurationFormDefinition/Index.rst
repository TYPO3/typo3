.. include:: /Includes.rst.txt


.. _concepts-formdefinition-vs-formconfiguration:

Form configuration vs. form definition
======================================

Up to this point, we have mainly looked at form framework configuration.
In short, **form configuration** is based on *prototypes* and allows you to define:

- which form elements, finishers, and validators are available to the system,
- how they are pre-configured,
- how they are displayed in the frontend and backend.

However, a second important part of the form framework is **form definition**,
which is configuration but for *specific* forms, for example the ones users define. Form
definition includes:

- form elements and their validators,
- the order of the form elements on the form
- the finishers that are fired when the form is submitted
- values of form element properties.


In other words, a ``Text`` form element would be defined in **form configuration**
but a ``Text`` form element located on page 1 at position 1 of a specific form
would be defined in a **form definition**. A **form definition** might also define
a placeholder (HTML attribute) with a value of "Your name
here" in a form element. Form definitions are created by the backend ``form editor``.

Example form definition (for a specific form)
---------------------------------------------

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
