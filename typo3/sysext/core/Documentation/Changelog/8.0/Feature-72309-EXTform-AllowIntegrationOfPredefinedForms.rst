
.. include:: ../../Includes.txt

============================================================
Feature: #72309 - EXT:form - Integration of Predefined Forms
============================================================

See :issue:`72309`

Description
===========

The content element of EXT:form now allows the integration of predefined forms. An integrator can
define forms - for example within a site package - using `plugin.tx_form.predefinedForms`. An
editor can add a new `mailform` content element to a page and choose a form from a list of
predefined elements.

There are even more advantages:

*  Integrators can build there forms with TypoScript which offers much more
   possibilities than doing it within the form wizard. Especially, the
   integrator is able to use stdWrap functionality which are not available when
   using the form wizard (for security reasons).

*  There is no need anymore for editors to use the form wizard. They can choose the
   predefined forms which are optimized layout-wise.

*  Forms can be re-used throughout the whole installation.

*  Forms can be stored outside the DB and versioned.

In order to be able to select the pre-defined form in the backend, the form has to be registered
using PageTS.

.. code-block:: typoscript

   TCEFORM.tt_content.tx_form_predefinedform.addItems.contactForm = LLL:EXT:my_theme/Resources/Private/Language/locallang.xlf:contactForm

Example form:

.. code-block:: typoscript

   plugin.tx_form.predefinedForms.contactForm = FORM
   plugin.tx_form.predefinedForms.contactForm {
      enctype = multipart/form-data
      method = post
      prefix = contact
      confirmation = 1

      postProcessor {
         1 = mail
         1 {
            recipientEmail = test@mail.com
            senderEmail = test@mail.com
            subject {
               value = Contact form
               lang.de = Kontakt Formular
            }
         }
      }

      10 = TEXTLINE
      10 {
         name = name
         type = text
         required = required
         label {
            value = Name
            lang.de = Name
         }
         placeholder {
            value = Enter your name
            lang.de = Name eingeben
         }
      }

      20 = TEXTLINE
      20 {
         name = email
         type = email
         required = required
         label {
            value = Email
            lang.de = E-Mail
         }
         placeholder {
            value = Enter your email address
            lang.de = E-Mail Adresse eingeben
         }
      }

      30 = TEXTAREA
      30 {
         name = message
         cols = 40
         rows = 5
         required = required
         label {
            value = Message
            lang.de = Nachricht
         }
         placeholder {
            value = Enter your message
            lang.de = Nachricht eingeben
         }
      }

      40 = SUBMIT
      40 {
         name = 5
         type = submit
         value {
            value = Send
            lang.de = Senden
         }
      }
   }

..

.. index:: ext:form, TypoScript, TSConfig
