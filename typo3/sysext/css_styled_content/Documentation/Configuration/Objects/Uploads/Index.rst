.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _objects-uploads:

File links / Uploads
""""""""""""""""""""

Since TYPO3 CMS 6.0 the file links content element types simply uses the FAL-related
:ref:`FILES <t3tsref:cobj-files>` for rendering.

.. important::

   Read what follows only if you use the static templates of
   css\_styled\_content for version 4.7 or below (see
   :ref:`installation`).

Before that it was rendered using a :ref:`USER <t3tsref:cobj-user>` object provided by
CSS Styled Content, as can be seen in the TypoScript setup::

	tt_content.uploads = COA
	tt_content.uploads {
		10 = < lib.stdheader

		20 = USER
		20.userFunc = \TYPO3\CMS\CssStyledContent\Controller\CssStyledContentController->render_uploads
		...
	}


The :code:`render_uploads` function supports a whole variety of specific
properties, which are detailed below.

.. note::

   All properties of USER objects also apply, in particular :ref:`stdWrap <t3tsref:stdwrap>`.


.. _objects-uploads-reference:

Reference
~~~~~~~~~


.. _objects-uploads-reference-filepath:

filePath
''''''''

.. container:: table-row

   Property
         filePath

   Data type
         string / :ref:`stdWrap <t3tsref:stdwrap>`

   Description
         The path to the files to read out.

   Default
         field = select\_key



.. _objects-uploads-reference-field:

field
'''''

.. container:: table-row

   Property
         field

   Data type
         string

   Description
         The field to fetch the content from.

   Default
         media



.. _objects-uploads-reference-linkproc:

linkProc
''''''''

.. container:: table-row

   Property
         linkProc

   Data type
         Array of options listed below


   Description
         The link processing options.

         **Example:**

         ::

            target = _blank
            jumpurl = {$styles.content.uploads.jumpurl}
            jumpurl.secure = {$styles.content.uploads.jumpurl_secure}
            jumpurl.secure.mimeTypes= {$styles.content.uploads.jumpurl_secure_mimeTypes}
            removePrependedNumbers = 1
            iconCObject = IMAGE
            iconCObject.file.import.data = register : ICON_REL_PATH
            iconCObject.file.width = 150
            ATagParams = class="external-link-new-window"

   Default
         See example



.. _objects-uploads-reference-labelstdwrap:

labelStdWrap
''''''''''''

.. container:: table-row

   Property
         labelStdWrap

   Data type
         :ref:`stdWrap <t3tsref:stdwrap>`

   Description
         Provides a mean to override the default text that is linked in the
         "linkedLabel" registry for each itemRendering. Registry items
         filename, path, description, fileSize and fileExtension are available
         at this point.

         **Example:**

         ::

            tt_content.uploads.20.labelStdWrap.override.data = register:description


.. _objects-uploads-reference-filesize:

fileSize
''''''''

.. container:: table-row

   Property
         fileSize

   Data type


   Description
         Display options for file size.

   Default
         bytes = 1

         bytes.labels = {$styles.content.uploads.filesizeBytesLabels}



.. _objects-uploads-reference-itemrendering:

itemRendering
'''''''''''''

.. container:: table-row

   Property
         itemRendering

   Data type
         :ref:`cObj <t3tsref:cobjects>` / + :ref:`optionSplit <t3tsref:objects-optionsplit>`

   Description
         Provides the rendering information for every row in the filelist.
         Each file will be rendered with this cObject, optionSplit will be
         applied to the whole itemRendering array so that different rendering
         needs can be applied to individual rows. Default rendering in
         css\_styled\_content is a :ref:`COA <t3tsref:cobj-coa-int>` for table based rendering with even/odd
         classes in the rows.

         **Available registers at this point are:**

         - linkedIcon: a linked icon representing the file (either extension-
           dependent or a thumbnail of the image)

         - linkedLabel: the linked text, usually the filename. The text can be
           overwritten using the labelStdWrap property.

         - filename: the filename being rendered (with extension, but without
           path)

         - path: the full path of the file

         - description: optional, if available

         - fileSize: the size of the file in bytes

         - fileExtension: the extension of the file (e.g. "pdf", "gif", etc)

         **Example:**

         ::

            itemRendering = COA
            itemRendering {
                    wrap = <tr class="tr-odd tr-first">|</tr> |*| <tr class="tr-even">|</tr> || <tr class="tr-odd">|</tr> |*|

                    10 = TEXT
                    10.data = register:linkedIcon
                    10.wrap = <td class="csc-uploads-icon">|</td>
                    10.if.isPositive.field = layout

                    20 = COA
                    20.wrap = <td class="csc-uploads-fileName">|</td>
                    20.1 = TEXT
                    20.1 {
                            data = register:linkedLabel
                            wrap = <p>|</p>
                    }
                    20.2 = TEXT
                    20.2 {
                            data = register:description
                            wrap = <p class="csc-uploads-description">|</p>
                            required = 1
                    }

                    30 = TEXT
                    30.if.isTrue.field = filelink_size
                    30.data = register:fileSize
                    30.wrap = <td class="csc-uploads-fileSize">|</td>
                    30.bytes = 1
                    30.bytes.labels = {$styles.content.uploads.filesizeBytesLabels}
            }
