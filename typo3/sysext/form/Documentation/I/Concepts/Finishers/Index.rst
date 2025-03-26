.. include:: /Includes.rst.txt


.. _concepts-finishers:

Finishers
=========

The form framework ships a bunch of finishers, which will be briefly
described here. For more details, please head to the API reference and check
out the section regarding :ref:`Finisher Options<apireference-finisheroptions>`.

.. important::

   Finishers are executed in the order defined in your form definition. This is
   especially important when you are using the ``Redirect finisher``. Make sure
   this finisher is the very last one to be executed. The ``Redirect finisher``
   stops the execution of all subsequent finishers in order to perform the redirect.
   I.e. finishers defined after the ``Redirect finisher`` will not be executed in
   any case.


.. _concepts-finishers-closurefinisher:

Closure finisher
----------------

The 'Closure finisher' can only be used within forms that are created
programmatically. It allows you to execute your own finisher code without
implementing/ declaring a finisher.


.. _concepts-finishers-confirmationfinisher:

Confirmation finisher
---------------------

The 'Confirmation finisher' is a simple finisher that outputs a given
text after the form has been submitted.


.. _concepts-finishers-deleteuploadsfinisher:

DeleteUploads finisher
----------------------

The 'DeleteUploads finisher' removes submitted files. Use this finisher,
for example, after the email finisher if you do not want to keep the files
within your TYPO3 installation.

.. note::

   Finishers are only executed on successfully submitted forms. If a user uploads
   a file but does not finish the form successfully, the uploaded files will not
   be deleted.


.. _concepts-finishers-emailfinisher:

Email finisher
--------------

The :php:`EmailFinisher` sends an email to one recipient. EXT:form uses two
:php:`EmailFinisher` declarations with the identifiers ``EmailToReceiver`` and
``EmailToSender``.


.. _concepts-finishers-emailfinisher-bcc-recipients:

Working with BCC recipients
"""""""""""""""""""""""""""

Both email finishers support different recipient types, including Carbon Copy
(CC) and Blind Carbon Copy (BCC). Depending on the configuration of the server
and the TYPO3 instance, it may not be possible to send emails to BCC recipients.
The configuration of the :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_sendmail_command']`
value is crucial. As documented in :ref:`CORE API <t3coreapi:mail-configuration-sendmail>`,
TYPO3 recommends the parameter :php:`-bs` (instead of :php:`-t -i`) when using
:php:`sendmail`. The parameter :php:`-bs` tells TYPO3 to use the SMTP standard
and that way the BCC recipients are properly set. `Symfony <https://symfony.com/doc/current/mailer.html#using-built-in-transports>`__
refers to the problem of using the :php:`-t` parameter as well. Since TYPO3 7.5
(`#65791 <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/7.5/Feature-65791-UsePHPConfiguredSendmailPathIfMAILtransportSendmailIsActive.html>`__)
the :php:`transport_sendmail_command` is automatically set from the PHP runtime
configuration and saved. Thus, if you have problems with sending emails to BCC
recipients, check the above mentioned configuration.


.. _concepts-finishers-emailfinisher-fluidemail:

About FluidEmail
""""""""""""""""

.. versionchanged:: 12.0
    The :php:`EmailFinisher` always sends email via :php:`FluidEmail`.

FluidEmail allows to send mails in a standardized way.

The option :yaml:`title` is available which can
be used to add an email title to the default FluidEmail template. This option is
capable of rendering form element variables using the known bracket syntax and can
be overwritten in the FlexForm configuration of the form plugin.

To customize the templates being used following options can be set:

*   :yaml:`templateName`: The template name (for both HTML and plaintext) without the
    extension
*   :yaml:`templateRootPaths`: The paths to the templates
*   :yaml:`partialRootPaths`: The paths to the partials
*   :yaml:`layoutRootPaths`: The paths to the layouts

.. note::
    The formerly available field :yaml:`templatePathAndFilename` is not evaluated
    anymore.

A finisher configuration could look like this:

.. code-block:: yaml

   identifier: contact
   type: Form
   prototypeName: standard
   finishers:
   -
     identifier: EmailToSender
     options:
       subject: 'Your Message: {message}'
       title: 'Hello {name}, your confirmation'
       templateName: ContactForm
       templateRootPaths:
         100: 'EXT:my_site_package/Resources/Private/Templates/Email/'
       partialRootPaths:
         100: 'EXT:my_site_package/Resources/Private/Partials/Email/'
       addHtmlPart: true

In the example above the following files must exist in the specified
template path:

*   :file:`ContactForm.html`
*   :file:`ContactForm.txt`


.. _concepts-finishers-flashmessagefinisher:

FlashMessage finisher
---------------------

The 'FlashMessage finisher' is a simple finisher that adds a message to the
FlashMessageContainer.


.. _concepts-finishers-redirectfinisher:

Redirect finisher
-----------------

The 'Redirect finisher' is a simple finisher that redirects to another page.
Additional link parameters can be added to the URL.

.. important::

   This finisher stops the execution of all subsequent finishers in order to perform
   the redirect. Therefore, this finisher should always be the last finisher to be
   executed.


.. _concepts-finishers-savetodatabasefinisher:

SaveToDatabase finisher
-----------------------

The 'SaveToDatabase finisher' saves the data of a submitted form into a
database table.

Here is an example for adding uploads to ext:news (fal_related_files and fal_media).

.. code-block:: yaml

  -
    identifier: SaveToDatabase
    options:
      -
        table: tx_news_domain_model_news
        mode: insert
        elements:
          my-field:
            mapOnDatabaseColumn: bodytext
          imageupload-1:
            mapOnDatabaseColumn: fal_media
          fileupload-1:
            mapOnDatabaseColumn: fal_related_files
        databaseColumnMappings:
          pid:
            value: 3
          tstamp:
            value: '{__currentTimestamp}'
          datetime:
            value: '{__currentTimestamp}'
          crdate:
            value: '{__currentTimestamp}'
          hidden:
            value: 1
      -
        table: sys_file_reference
        mode: insert
        elements:
          imageupload-1:
            mapOnDatabaseColumn: uid_local
            skipIfValueIsEmpty: true
        databaseColumnMappings:
          tablenames:
            value: tx_news_domain_model_news
          fieldname:
            value: fal_media
          tstamp:
            value: '{__currentTimestamp}'
          crdate:
            value: '{__currentTimestamp}'
          showinpreview:
            value: 1
          uid_foreign:
            value: '{SaveToDatabase.insertedUids.0}'
      -
        table: sys_file_reference
        mode: insert
        elements:
          fileupload-1:
            mapOnDatabaseColumn: uid_local
            skipIfValueIsEmpty: true
        databaseColumnMappings:
          tablenames:
            value: tx_news_domain_model_news
          fieldname:
            value: fal_related_files
          tstamp:
            value: '{__currentTimestamp}'
          crdate:
            value: '{__currentTimestamp}'
          uid_foreign:
            value: '{SaveToDatabase.insertedUids.0}'
      -
        table: sys_file_reference
        mode: update
        whereClause:
          uid_foreign: '{SaveToDatabase.insertedUids.0}'
          uid_local: 0
        databaseColumnMappings:
           pid:
             value: 0
           uid_foreign:
             value: 0

.. _concepts-finishers-translation:

Translation of finisher options
-------------------------------

To learn more about this topic, please continue :ref:`here<concepts-frontendrendering-translation-finishers>`.


.. _concepts-finishers-customfinisherimplementations:

Write a custom finisher
-----------------------

If you want to make the finisher configurable in the backend UI read :ref:`here<concepts-finishers-customfinisherimplementations-extend-gui>`.

Finishers are defined as part of a ``prototype`` within a
``finishersDefinition``. The property ``implementationClassName`` is to be
utilized to load the finisher implementation.

.. code-block:: yaml

   prototypes:
     standard:
       finishersDefinition:
         CustomFinisher:
           implementationClassName: 'VENDOR\MySitePackage\Domain\Finishers\CustomFinisher'

If the finisher requires options, you can define those within the
``options`` property. The options will be used as default values and can
be overridden using the ``form definition``.

Define the default value:

.. code-block:: yaml

   prototypes:
     standard:
       finishersDefinition:
         CustomFinisher:
           implementationClassName: 'VENDOR\MySitePackage\Domain\Finishers\CustomFinisher'
           options:
             yourCustomOption: 'Ralf'

Override the option using the ``form definition``:

.. code-block:: yaml

   identifier: sample-form
   label: 'Simple Contact Form'
   prototype: standard
   type: Form

   finishers:
     -
       identifier: CustomFinisher
       options:
         yourCustomOption: 'Björn'

   renderables:
     ...

Each finisher has to be programmed to the interface ``TYPO3\CMS\Form\Domain\Finishers\FinisherInterface``
and should extend the class ``TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher``.
In doing so, the logic of the finisher should start with the method
``executeInternal()``.


.. _concepts-finishers-customfinisherimplementations-accessingoptions:

Accessing finisher options
""""""""""""""""""""""""""

If your finisher extends ``TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher``,
you can access your finisher options with the help of the ``parseOption()``
method::

   $yourCustomOption = $this->parseOption('yourCustomOption');

``parseOption()`` is looking for 'yourCustomOption' in your
``form definition``. If it cannot be found, the method checks

1. the ``prototype`` configuration for a default value,

2. the finisher class itself by searching for a default value within the
   ``$defaultOptions`` property::

      declare(strict_types = 1);
      namespace VENDOR\MySitePackage\Domain\Finishers;

      class CustomFinisher extends \TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher
      {

          protected $defaultOptions = [
              'yourCustomOption' => 'Olli',
          ];

          // ...
      }

If the option cannot be found by processing this fallback chain, ``null`` is
returned.

If the option is found, the process checks whether the option value will
access :ref:`FormRuntime values<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`.
If the ``FormRuntime`` returns a positive result, it is checked whether the
option value :ref:`can access values of preceding finishers<concepts-finishers-customfinisherimplementations-finishercontext-sharedatabetweenfinishers>`.
At the very end, it tries to :ref:`translate the finisher options<concepts-frontendrendering-translation-finishers>`.


.. _concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor:

Accessing form runtime values
'''''''''''''''''''''''''''''

By utilizing a specific notation, finisher options can be populated with
submitted form values (assuming you are using the ``parseOption()`` method).
You can access values of the ``FormRuntime`` and thus values of each single
form element by encapsulating the option values with ``{}``. If there is a
form element with the ``identifier`` 'subject', you can access its value
within the finisher configuration. Check out the following example to
get the whole idea.

.. code-block:: yaml

   identifier: simple-contact-form
   label: 'Simple Contact Form'
   prototype: standard
   type: Form

   finishers:
     -
       identifier: Custom
       options:
         yourCustomOption: '{subject}'

   renderables:
     -
       identifier: subject
       label: 'Subject'
       type: Text

::

   // $yourCustomOption contains the value of the form element with the
   // identifier 'subject'
   $yourCustomOption = $this->parseOption('yourCustomOption');

In addition, you can use ``{__currentTimestamp}`` as a special option value.
It will return the current UNIX timestamp.


.. _concepts-finishers-customfinisherimplementations-finishercontext:

Finisher Context
""""""""""""""""

The class ``TYPO3\CMS\Form\Domain\Finishers\FinisherContext`` takes care of
transferring a finisher context to each finisher. Given the finisher is
derived from ``TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`` the
finisher context will be available via::

   $this->finisherContext

The method ``cancel`` prevents the execution of successive finishers::

   $this->finisherContext->cancel();

The method ``getFormValues`` returns all of the submitted form values.

``getFormValues``::

   $this->finisherContext->getFormValues();

The method ``getFormRuntime`` returns the ``FormRuntime``::

   $this->finisherContext->getFormRuntime();


.. _concepts-finishers-customfinisherimplementations-finishercontext-sharedatabetweenfinishers:

Share data between finishers
''''''''''''''''''''''''''''

The method ``getFinisherVariableProvider`` returns an object (``TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider``)
which allows you to store data and transfer it to other finishers. The data
can be easily accessed programmatically or within your configuration::

   $this->finisherContext->getFinisherVariableProvider();

The data is stored within the ``FinisherVariableProvider`` and is addressed
by a user-defined 'finisher identifier' and a custom option value path. The
name of the 'finisher identifier' should consist of the name of the finisher
without the potential 'Finisher' appendix. If your finisher is derived from
the class ``TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher``, the name of
this construct is stored in the following variable::

   $this->shortFinisherIdentifier

For example, if the name of your finisher class is 'CustomFinisher', the
mentioned variable will contain the value 'Custom'.

There are a bunch of methods to access and manage the finisher data:

- Add data::

      $this->finisherContext->getFinisherVariableProvider()->add(
          $this->shortFinisherIdentifier,
          'unique.value.identifier',
          $value
      );

- Get data::

      $this->finisherContext->getFinisherVariableProvider()->get(
          $this->shortFinisherIdentifier,
          'unique.value.identifier',
          'default value'
      );

- Check the existence of data::

      $this->finisherContext->getFinisherVariableProvider()->exists(
          $this->shortFinisherIdentifier,
          'unique.value.identifier'
      );

- Delete data::

      $this->finisherContext->getFinisherVariableProvider()->remove(
          $this->shortFinisherIdentifier,
          'unique.value.identifier'
      );

In this way, each finisher can access data programmatically. Moreover, it is
possible to retrieve the data via configuration, provided that a finisher
stores the values within the ``FinisherVariableProvider``.

Assuming that a finisher called 'Custom' sets data as follows::

   $this->finisherContext->getFinisherVariableProvider()->add(
       $this->shortFinisherIdentifier,
       'unique.value.identifier',
       'Wouter'
   );

... you are now able to access the value 'Wouter' via ``{Custom.unique.value.identifier}``
in any other finisher.

.. code-block:: yaml

   identifier: sample-form
   label: 'Simple Contact Form'
   prototype: standard
   type: Form

   finishers:
     -
       identifier: Custom
       options:
         yourCustomOption: 'Frans'

     -
       identifier: SomeOtherStuff
       options:
         someOtherCustomOption: '{Custom.unique.value.identifier}'


.. _concepts-finishers-customfinisherimplementations-extend-gui:

Add finisher to backend UI
''''''''''''''''''''''''''

After adding a custom finisher you can also add the finisher to the
form editor GUI to let your backend users configure it visually. Add the
following to the backend yaml setup:

.. code-block:: yaml

   prototypes:
     standard:
       formElementsDefinition:
         Form:
           formEditor:
             editors:
               900:
                 # Extend finisher drop down
                 selectOptions:
                   35:
                     value: 'CustomFinisher'
                     label: 'Custom Finisher'
             propertyCollections:
               finishers:
                  # add finisher fields
                  25:
                     identifier: 'CustomFinisher'
                     editors:
                        100:
                          identifier: header
                          templateName: Inspector-CollectionElementHeaderEditor
                          label: "Custom Finisher"
                        # custom field (input, required)
                        110:
                          identifier: 'customField'
                          templateName: 'Inspector-TextEditor'
                          label: 'Custom Field'
                          propertyPath: 'options.customField'
                          propertyValidators:
                            10: 'NotEmpty'
                        # email field
                        120:
                          identifier: 'email'
                          templateName: 'Inspector-TextEditor'
                          label: 'Subscribers email'
                          propertyPath: 'options.email'
                          enableFormelementSelectionButton: true
                          propertyValidators:
                            10: 'NotEmpty'
                            20: 'FormElementIdentifierWithinCurlyBracesInclusive'
                        9999:
                          identifier: removeButton
                          templateName: Inspector-RemoveElementEditor
          finishersDefinition:
            CustomFinisher:
              formEditor:
                iconIdentifier: 'form-finisher'
                label: 'Custom Finisher'
                predefinedDefaults:
                  options:
                    customField: ''
                    email: ''
              # displayed when overriding finisher settings
              FormEngine:
                label: 'Custom Finisher'
                elements:
                  customField:
                    label: 'Custom Field'
                    config:
                      type: 'text'
                  email:
                    label: 'Subscribers email'
                    config:
                      type: 'text'

Make sure the setup file is registered for the backend in a :file:`EXT:my_extension/ext_localconf.php` file:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_form.settings.yamlConfigurations {
            123456789 = EXT:yourExtension/Configuration/Form/Backend.yaml
        }
    ');
