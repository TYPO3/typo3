.. include:: ../../Includes.txt



.. _configuration:

Configuration Options
^^^^^^^^^^^^^^^^^^^^^

There are a number of User TSconfig and Page TSconfig properties related to workspaces.

.. note::

   Some of these options are actually handled by the "version" system extension,
   but apply to workspaces.


.. only:: html

   .. contents::
      :local:
      :depth: 3


.. _pagetsconfig:

Page TSconfig
"""""""""""""

.. _pagetsconfig-workspaces:

workspaces
~~~~~~~~~~


.. _pagetsconfig-workspaces-splitpreviewmodes:

splitPreviewModes
*****************

Data type
  String

Description
  Comma-separated list of allowed values for preview modes. Possible
  values are "slider", "vbox" and "hbox".

  **Example**

  .. code-block:: typoscript

     workspaces.splitPreviewModes = slider

  will restrict the preview mode to "slider".

Default
  slider, vbox, hbox

.. _pagetsconfig-options-workspaces:

options.workspaces
~~~~~~~~~~~~~~~~~~


.. _pagetsconfig-options-workspaces-previewpageid:

previewPageId
*************

Data type
  Integer / fieldReference per table

Description
  Page uid used for previewing records on a workspace.

  **Examples**

  .. code-block:: typoscript

     # Using page 123 for previewing workspaces records (in general)
     options.workspaces.previewPageId = 123

     # Using the pid field of each record for previewing (in general)
     options.workspaces.previewPageId = field:pid

     # Using page 123 for previewing workspaces records (for table tx_myext_table)
     options.workspaces.previewPageId.tx_myext_table = 123

     # Using the pid field of each record for previewing (for table tx_myext_table)
     options.workspaces.previewPageId.tx_myext_table = field:pid


.. _pagetsconfig-options-workspaces-enablemassactions:

enableMassActions
*****************

Data type
  Boolean

Description
  If set to "0", the mass actions menu will not be available to users.

Default
  1


.. _pagetsconfig-txversion-workspaces:

tx_version.workspaces
~~~~~~~~~~~~~~~~~~~~~


.. _pagetsconfig-txversion-workspaces-stagenotificationemail-subject:

stageNotificationEmail.subject
******************************

(:code:`tx_version.workspaces.stageNotificationEmail.subject`)

Data type
  String / Localized string reference (using :code:`LLL:` syntax).

Description
         The default subject for the stage notification email.

         The following markers can be used for replacement:

         - :code:`###SITE_NAME###`

         - :code:`###SITE_URL###`

         - :code:`###WORKSPACE_TITLE###`

         - :code:`###WORKSPACE_UID###`

         - :code:`###ELEMENT_NAME###`

         - :code:`###NEXT_STAGE###`

         - :code:`###COMMENT###`

         - :code:`###USER_REALNAME###`

         - :code:`###USER_USERNAME###`

         - :code:`###RECORD_PATH###`

         - :code:`###RECORD_TITLE###`

Default
  LLL:EXT:version/Resources/Private/Language/emails.xml:subject



.. _pagetsconfig-txversion-workspaces-stagenotificationemail-message:

stageNotificationEmail.message
******************************

(:code:`tx_version.workspaces.stageNotificationEmail.message`)

Data type
  String / Localized string reference (using :code:`LLL:` syntax).

Description
  The default message for the stage notification email.
  The same markers are available as for the subject (see above).

Default
  LLL:EXT:version/Resources/Private/Language/emails.xml:message


.. _usertsconfig:

User TSconfig
"""""""""""""


.. _usertsconfig-options-workspaces:

options.workspaces
~~~~~~~~~~~~~~~~~~


.. _usertsconfig-options-workspaces-previewlinkttlhours:

previewLinkTTLHours
*******************

Data type
  Integer

Description
  Number of hours until expiry of preview links to workspaces.

Default
  48


.. _usertsconfig-options-workspaces-swapmode:

swapMode
********

Data type
  String

Description
  Possible values are:

  "any" - if page or element (meaning any record on the page) is
  published, all content elements on the page and page itself will be
  published regardless of the current editing stage.

  "page" - if page is published, all content elements on the page will
  be published as well. If element is published, its publishing does not
  affect other elements or page.


.. _usertsconfig-options-workspaces-changestagemode:

changeStageMode
***************

Data type
  String

Description
  Possible values are:

  "any" - if page or element (meaning any record on the page) stage is
  changed (for example, from "editing" to "review"), all content
  elements on the page and page will change to that new stage as well
  (possibly bypassing intermediate stages).

  "page" - if page stage is changed (for example, from "editing" to
  "review"), all content elements on the page will change stage as well
  (possibly bypassing intermediate stages). If stage is changed for
  element, all other elements on the page and page itself remain in the
  previous stage.


.. _usertsconfig-options-workspaces-considerreferences:

considerReferences
******************

Data type
  Boolean

Description
  If elements which are part of an interdependent structure (e.g. Inline
  Relational Record Editing) are swapped, published or sent to a stage
  alone, the whole related parent/child structure is taken into account
  automatically.

Default
  1

allowed\_languages
******************

   Property
         workspaces.allowed\_languages.[workspaceId]

Data type
  *(list of sys\_language ids)*

Description
  This is a list of sys\_language uids which will be allowed in a
  workspace. This list - if set - will override the allowed languages
  list in the BE user group configuration.

  **Example**

  .. code-block:: typoscript

     options.workspaces.allowed_languages.3 = 1,2

  In this example, the user will be restricted to languages with "uid" 1 or 2
  in the workspace with "uid" 3.
