.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _classesanchor:

classesAnchor:
""""""""""""""

The following property allows to configure the anchor accessibility
feature:


.. _classesanchor-id-string:

classesAnchor.[ *id-string* ]
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         classesAnchor.[ *id-string* ]
   
   Description
         Attaches special properties to the classes available in the
         Insert/Modify link dialog.
         
         Properties:
         
         .class = CSS-class-name: the name of the CSS class to which the
         properties are attached
         
         .type = page, url, file, mail or spec: specifies that the class
         applies to anchors for internal pages, external URL's, files, email
         addresses or special user-defined links respectively; the class will
         be presented only in the corresponding tab of the 'Insert/Modify link'
         dialogue
         
         .image = URL of an icon file that will prefix or postfix the content
         of the anchor when the class is applied to an anchor; the TYPO3 syntax
         EXT:extension-key/sub-directory/image-file-name may be used
         
         .addIconAfterLink = boolean: if set, the content of the link is
         postfixed with the icon; default is to prefix the content of the link
         with the icon
         
         .altText = the text that will be used as altText for the image when
         the class is applied to an anchor; may be language-splitted; the TYPO3
         syntax LLL:EXT:extension-key/sub-directory/locallang.xlf:label-index
         may also be used in order for the text to be localized to the language
         of the content using the specified language file and label index
         
         .titleText = the text that will be used as title for the anchor when
         the class is applied to an anchor; may be language-splitted;the TYPO3
         syntax LLL:EXT:extension-key/sub-directory/locallang.xlf:label-index
         may also be used in order for the text to be localized to the language
         of the content using the specified language file and label index
         
         .target = string; if set, this is the default value to be assigned to
         the target attribute of the link when the class is applied to the link
         
         See the Demo default configuration for a complete example.


[page:RTE]

