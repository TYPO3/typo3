.. include:: /Includes.rst.txt


.. _apireference-finisheroptions:

Finisher Options
================

.. _apireference-finisheroptions-closurefinisher:

Closure finisher
----------------

This finisher can only be used in programmatically-created forms. It makes it
possible to execute one's own finisher code without having to implement/
declare this finisher.

Usage through code::

   $closureFinisher = GeneralUtility::makeInstance(ClosureFinisher::class);
   $closureFinisher->setOption('closure', function($finisherContext) {
       $formRuntime = $finisherContext->getFormRuntime();
       // ...
   });
   $formDefinition->addFinisher($closureFinisher);


.. _apireference-finisheroptions-closurefinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-closurefinisher-options-closure:

closure
+++++++

:aspect:`Data type`
      \Closure

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null


.. _apireference-finisheroptions-confirmationfinisher:

Confirmation finisher
---------------------

A simple finisher that outputs a given text or a content element, respectively.

Usage within form definition for the case, you want to use a given text.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: Confirmation
       options:
         message: 'Thx for using TYPO3'
   ...


Usage through code::

   $formDefinition->createFinisher('Confirmation', [
       'message' => 'foo',
   ]);

or create manually (not preferred)::

   $confirmationFinisher = GeneralUtility::makeInstance(ConfirmationFinisher::class);
   $confirmationFinisher->setOptions([
       'message' => 'foo',
   ]);
   $formDefinition->addFinisher($confirmationFinisher);


Usage within form definition for the case, you want to output a content element.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: Confirmation
       options:
         contentElement: 9
   ...


Usage through code::

   $formDefinition->createFinisher('Confirmation', [
       'contentElement' => 9,
   ]);

or create manually (not preferred)::

   $confirmationFinisher = GeneralUtility::makeInstance(ConfirmationFinisher::class);
   $confirmationFinisher->setOptions([
       'contentElement' => 9,
   ]);
   $formDefinition->addFinisher($confirmationFinisher);


.. _apireference-finisheroptions-confirmationfinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-confirmationfinisher-options-message:

message
+++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      The form has been submitted.


.. _apireference-finisheroptions-deleteuploadsfinisher:

DeleteUploads finisher
----------------------

This finisher remove the currently submited files.
Use this finisher e.g after the email finisher if you don't want to keep the files online.


Usage within form definition.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: DeleteUploads
   ...


Usage through code::

   $formDefinition->createFinisher('DeleteUploads');

or create manually (not preferred)::

   $deleteUploadsFinisher = GeneralUtility::makeInstance(DeleteUploadsFinisher::class);
   $formDefinition->addFinisher($deleteUploadsFinisher);


.. _apireference-finisheroptions-emailfinisher:

Email finisher
--------------

This finisher sends an email to one recipient.
EXT:form uses 2 EmailFinisher declarations with the identifiers ``EmailToReceiver`` and ``EmailToSender``.

Usage within form definition.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: EmailToReceiver
       options:
         subject: 'Your message'
         recipients:
           your.company@example.com: 'Your Company name'
           ceo@example.com: 'CEO'
         senderAddress: 'form@example.com'
         senderName: 'form submitter'
   ...


Usage through code::

   $formDefinition->createFinisher('EmailToReceiver', [
       'subject' => 'Your message',
       'recipients' => [
           'your.company@example.com' => 'Your Company name',
           'ceo@example.com' => 'CEO'
       ],
       'senderAddress' => 'form@example.com',
       'senderName' => 'form submitter',
   ]);

or create manually (not preferred)::

   $emailFinisher = GeneralUtility::makeInstance(EmailFinisher::class);
   $emailFinisher->setOptions([
       'subject' => 'Your message',
       'recipients' => [
           'your.company@example.com' => 'Your Company name',
           'ceo@example.com' => 'CEO'
       ],
       'senderAddress' => 'form@example.com',
       'senderName' => 'form submitter',
   ]);
   $formDefinition->addFinisher($emailFinisher);


.. _apireference-finisheroptions-emailfinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-emailfinisher-options-subject:

subject
+++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Subject of the email.


.. _apireference-finisheroptions-emailfinisher-options-recipients:

recipients
++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email addresses and names of the recipients (To).


.. _apireference-finisheroptions-emailfinisher-options-senderaddress:

senderAddress
+++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email address of the sender/ visitor (From).


.. _apireference-finisheroptions-emailfinisher-options-sendername:

senderName
++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Description`
      Human-readable name of the sender.


.. _apireference-finisheroptions-emailfinisher-options-replytorecipients:

replyToRecipients
+++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email addresses of to be used as reply-to emails.


.. _apireference-finisheroptions-emailfinisher-options-carboncopyrecipients:

carbonCopyRecipients
++++++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email addresses of the copy recipient.


.. _apireference-finisheroptions-emailfinisher-options-blindcarbonCopyrecipients:

blindCarbonCopyRecipients
+++++++++++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email address of the blind copy recipient.


.. _apireference-finisheroptions-emailfinisher-options-addhtmlpart:

addHtmlPart
+++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      true

:aspect:`Description`
      If set, mails will contain a plaintext and HTML part, otherwise only a
      plaintext part. That way, it can be used to disable HTML and enforce
      plaintext-only mails.


.. _apireference-finisheroptions-emailfinisher-options-attachuploads:

attachUploads
+++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      true

:aspect:`Description`
      If set, all uploaded items are attached to the email.


.. _apireference-finisheroptions-emailfinisher-options-translation-title:

title
+++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      The title, being shown in the Email.


.. _apireference-finisheroptions-emailfinisher-options-translation-language:

translation.language
++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      If not set, the finisher options are translated depending on the current frontend language (if translations exists).
      This option allows you to force translations for a given language isocode, e.g 'dk' or 'de'.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _apireference-finisheroptions-emailfinisher-options-translation-translationfiles:

translation.translationFiles
++++++++++++++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _apireference-finisheroptions-emailfinisher-options-layoutrootpaths:

layoutRootPaths
+++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Fluid layout paths


.. _apireference-finisheroptions-emailfinisher-options-partialrootpaths:

partialRootPaths
++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Fluid partial paths


.. _apireference-finisheroptions-emailfinisher-options-templaterootpaths:

templateRootPaths
+++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Fluid template paths; all templates get the current :php:`FormRuntime`
      assigned as :code:`form` and the :php:`FinisherVariableProvider` assigned
      as :code:`finisherVariableProvider`.


.. _apireference-finisheroptions-emailfinisher-options-variables:

variables
+++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      associative array of variables which are available inside the Fluid template


.. _apireference-finisheroptions-flashmessagefinisher:

FlashMessage finisher
---------------------

A simple finisher that adds a message to the FlashMessageContainer.

Usage within form definition.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: FlashMessage
       options:
         messageTitle: 'Merci'
         messageCode: 201905041245
         messageBody: 'Thx for using %s'
         messageArguments:
           - 'TYPO3'
         severity: 0
   ...


Usage through code::

   $formDefinition->createFinisher('FlashMessage', [
       'messageTitle' => 'Merci',
       'messageCode' => 201905041245,
       'messageBody' => 'Thx for using %s',
       'messageArguments' => ['TYPO3'],
       'severity' => \TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
   ]);

or create manually (not preferred)::

   $flashMessageFinisher = GeneralUtility::makeInstance(FlashMessageFinisher::class);
   $flashMessageFinisher->setOptions([
       'messageTitle' => 'Merci',
       'messageCode' => 201905041245,
       'messageBody' => 'Thx for using %s',
       'messageArguments' => ['TYPO3'],
       'severity' => \TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
   ]);
   $formDefinition->addFinisher($flashMessageFinisher);


.. _apireference-finisheroptions-flashmessagefinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-flashmessagefinisher-options-messagebody:

messageBody
+++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Description`
      The flash message body


.. _apireference-finisheroptions-flashmessagefinisher-options-messagetitle:

messageTitle
++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Description`
      The flash message title, if needed


.. _apireference-finisheroptions-flashmessagefinisher-options-messagearguments:

messageArguments
++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty array

:aspect:`Description`
      The flash message arguments, if needed


.. _apireference-finisheroptions-flashmessagefinisher-options-messagecode:

messageCode
+++++++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      Yes

:aspect:`Description`
      The flash message code


.. _apireference-finisheroptions-flashmessagefinisher-options-severity:

severity
++++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      No

:aspect:`Default value`
      \TYPO3\CMS\Core\Messaging\AbstractMessage::OK (0)

:aspect:`Description`
      The flash message severity code.
      See \TYPO3\CMS\Core\Messaging\AbstractMessage constants for the codes.


.. _apireference-finisheroptions-redirectfinisher:

Redirect finisher
-----------------

A simple finisher that redirects to another page.

Usage within form definition.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: Redirect
       options:
         pageUid: 1
         additionalParameters: 'param1=value1&param2=value2'
   ...


Usage through code::

   $formDefinition->createFinisher('Redirect', [
       'pageUid' => 1,
       'additionalParameters' => 'param1=value1&param2=value2',
   ]);

or create manually (not preferred)::

   $redirectFinisher = GeneralUtility::makeInstance(RedirectFinisher::class);
   $redirectFinisher->setOptions([
       'pageUid' => 1,
       'additionalParameters' => 'param1=value1&param2=value2',
   ]);
   $formDefinition->addFinisher($redirectFinisher);


.. _apireference-finisheroptions-redirectfinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-redirectfinisher-options-pageuid:

pageUid
+++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      1

:aspect:`Description`
      Redirect to this page uid


.. _apireference-finisheroptions-redirectfinisher-options-additionalparameters:

additionalParameters
++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Description`
      Additional parameters which should be used on the target page


.. _apireference-finisheroptions-redirectfinisher-options-delay:

delay
+++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      No

:aspect:`Default value`
      0

:aspect:`Description`
      The redirect delay in seconds.


.. _apireference-finisheroptions-redirectfinisher-options-statuscode:

statusCode
++++++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      No

:aspect:`Default value`
      303

:aspect:`Description`
      The HTTP status code for the redirect. Default is "303 See Other".


.. _apireference-finisheroptions-savetodatabasefinisher:

SaveToDatabase finisher
-----------------------

This finisher saves the data from a submitted form into a database table.


Usage within form definition.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: SaveToDatabase
       options:
         table: 'fe_users'
         mode: update
         whereClause:
           uid: 1
         databaseColumnMappings:
           tstamp:
             value: '{__currentTimestamp}'
           pid:
             value: 1
         elements:
           textfield-identifier-1:
             mapOnDatabaseColumn: 'first_name'
           textfield-identifier-2:
             mapOnDatabaseColumn: 'last_name'
           textfield-identifier-3:
             mapOnDatabaseColumn: 'username'
           advancedpassword-1:
             mapOnDatabaseColumn: 'password'
             skipIfValueIsEmpty: true
   ...


Usage through code::

   $formDefinition->createFinisher('SaveToDatabase', [
       'table' => 'fe_users',
       'mode' => 'update',
       'whereClause' => [
           'uid' => 1,
       ],
       'databaseColumnMappings' => [
           'pid' => ['value' => 1],
       ],
       'elements' => [
           'textfield-identifier-1' => ['mapOnDatabaseColumn' => 'first_name'],
           'textfield-identifier-2' => ['mapOnDatabaseColumn' => 'last_name'],
           'textfield-identifier-3' => ['mapOnDatabaseColumn' => 'username'],
           'advancedpassword-1' => [
               'mapOnDatabaseColumn' => 'password',
               'skipIfValueIsEmpty' => true,
           ],
       ],
   ]);

or create manually (not preferred)::

   $saveToDatabaseFinisher = GeneralUtility::makeInstance(SaveToDatabaseFinisher::class);
   $saveToDatabaseFinisher->setOptions([
       'table' => 'fe_users',
       'mode' => 'update',
       'whereClause' => [
           'uid' => 1,
       ],
       'databaseColumnMappings' => [
           'pid' => ['value' => 1],
       ],
       'elements' => [
           'textfield-identifier-1' => ['mapOnDatabaseColumn' => 'first_name'],
           'textfield-identifier-2' => ['mapOnDatabaseColumn' => 'last_name'],
           'textfield-identifier-3' => ['mapOnDatabaseColumn' => 'username'],
           'advancedpassword-1' => [
               'mapOnDatabaseColumn' => 'password',
               'skipIfValueIsEmpty' => true,
           ],
       ],
   ]);
   $formDefinition->addFinisher($saveToDatabaseFinisher);

You can write options as an array to perform multiple database operations.

Usage within form definition.

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: SaveToDatabase
       options:
         1:
           table: 'my_table'
           mode: insert
           databaseColumnMappings:
             some_column:
               value: 'cool'
         2:
           table: 'my_other_table'
           mode: update
           whereClause:
             pid: 1
           databaseColumnMappings:
             some_other_column:
               value: '{SaveToDatabase.insertedUids.1}'
   ...


Usage through code::

   $formDefinition->createFinisher('SaveToDatabase', [
       1 => [
           'table' => 'my_table',
           'mode' => 'insert',
           'databaseColumnMappings' => [
               'some_column' => ['value' => 'cool'],
           ],
       ],
       2 => [
           'table' => 'my_other_table',
           'mode' => 'update',
           'whereClause' => [
               'pid' => 1,
           ],
           'databaseColumnMappings' => [
               'some_other_column' => ['value' => '{SaveToDatabase.insertedUids.1}'],
           ],
       ],
   ]);

or create manually (not preferred)::

   $saveToDatabaseFinisher = GeneralUtility::makeInstance(SaveToDatabaseFinisher::class);
   $saveToDatabaseFinisher->setOptions([
       1 => [
           'table' => 'my_table',
           'mode' => 'insert',
           'databaseColumnMappings' => [
               'some_column' => ['value' => 'cool'],
           ],
       ],
       2 => [
           'table' => 'my_other_table',
           'mode' => 'update',
           'whereClause' => [
               'pid' => 1,
           ],
           'databaseColumnMappings' => [
               'some_other_column' => ['value' => '{SaveToDatabase.insertedUids.1}'],
           ],
       ],
   ]);
   $formDefinition->addFinisher($saveToDatabaseFinisher);


This performs 2 database operations.
One insert and one update.
You can access the inserted uids through '{SaveToDatabase.insertedUids.<theArrayKeyNumberWithinOptions>}'
If you perform a insert operation, the value of the inserted database row will be stored within the FinisherVariableProvider.
<theArrayKeyNumberWithinOptions> references to the numeric options.* key.


.. _apireference-finisheroptions-savetodatabasefinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-savetodatabasefinisher-options-table:

table
+++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

:aspect:`Description`
      Insert or update values into this table.


.. _apireference-finisheroptions-savetodatabasefinisher-options-mode:

mode
++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      'insert'

:aspect:`Possible values`
      insert/ update

:aspect:`Description`
      ``insert`` will create a new database row with the values from the submitted form and/or some predefined values. @see options.elements and options.databaseFieldMappings

      ``update`` will update a given database row with the values from the submitted form and/or some predefined values. 'options.whereClause' is then required.


.. _apireference-finisheroptions-savetodatabasefinisher-options-whereclause:

whereClause
+++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      Yes, if mode = update

:aspect:`Default value`
      empty array

:aspect:`Description`
      This where clause will be used for a database update action


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements:

elements
++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      empty array

:aspect:`Description`
      Use ``options.elements`` to map form element values to existing database columns.
      Each key within ``options.elements`` has to match with a form element identifier.
      The value for each key within ``options.elements`` is an array with additional informations.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-mapondatabasecolumn:

elements.<formElementIdentifier>.mapOnDatabaseColumn
++++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Description`
      The value from the submitted form element with the identifier ``<formElementIdentifier>`` will be written into this database column.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-skipifvalueisempty:

elements.<formElementIdentifier>.skipIfValueIsEmpty
+++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Description`
      Set this to true if the database column should not be written if the value from the submitted form element with the identifier
      ``<formElementIdentifier>`` is empty (think about password fields etc.). Empty means strings without content, whitespace is valid content.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-savefileidentifierinsteadofuid:

elements.<formElementIdentifier>.saveFileIdentifierInsteadOfUid
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Description`
      Set this to true if the database column should not be written if the value from the submitted form element with the identifier
      ``<formElementIdentifier>`` is empty (think about password fields etc.).

      This setting only rules for form elements which creates a FAL object like ``FileUpload`` or ``ImageUpload``.
      By default, the uid of the FAL object will be written into the database column. Set this to true if you want to store the
      FAL identifier (1:/user_uploads/some_uploaded_pic.jpg) instead.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-dateformat:

elements.<formElementIdentifier>.dateFormat
+++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      'U'

:aspect:`Description`
      If the internal datatype is :php:`\DateTime` which is true for the form element types
      :yaml:`DatePicker` and :yaml:`Date`, the object needs to be converted into a string value.
      This option allows you to define the format of the date in case of such a conversion.
      You can use every format accepted by the PHP :php:`date()` function (https://php.net/manual/en/function.date.php#refsect1-function.date-parameters).
      The default value is "U" which leads to a Unix timestamp.


.. _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings:

databaseColumnMappings
++++++++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty array

:aspect:`Description`
      Use this to map database columns to static values.
      Each key within ``options.databaseColumnMappings`` has to match with an existing database column.
      The value for each key within ``options.databaseColumnMappings`` is an array with additional informations.

      This mapping is done *before* the ``options.element`` mapping.
      This means if you map a database column to a value through ``options.databaseColumnMappings`` and map a submitted
      form element value to the same database column through ``options.element``, the submitted form element value
      will override the value you set within ``options.databaseColumnMappings``.


.. _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings.<databasecolumnname>.value:

databaseColumnMappings.<databaseColumnName>.value
+++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Description`
      The value which will be written to the database column.
      You can also use the :ref:`FormRuntime accessor feature<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>` to access every getable property from the ``FormRuntime``
      In short: use something like ``{<formElementIdentifier>}`` to get the value from the submitted form element with the identifier ``<formElementIdentifier>``.

      If you use the FormRuntime accessor feature within ``options.databaseColumnMappings``, the functionality is nearly identical
      to the ``options.elements`` configuration variant.


.. _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings.<databasecolumnname>.skipifvalueisempty:

databaseColumnMappings.<databaseColumnName>.skipIfValueIsEmpty
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Description`
      Set this to true if the database column should not be written if the value from ``options.databaseColumnMappings.<databaseColumnName>.value`` is empty.
