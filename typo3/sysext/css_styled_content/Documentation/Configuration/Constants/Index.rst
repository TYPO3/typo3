.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _constants:

Constants
^^^^^^^^^

.. note::

   Not all constants described here can be edited with the Constant Editors.
   Just define those in the "Constants" field of your TypoScript templates.


.. _constants-page-target:

PAGE\_TARGET
""""""""""""

.. container:: table-row

   Property
         PAGE\_TARGET

   Data type
         string

   Description
         Target for internal links: Should match the name of the content PAGE-
         object in TypoScript when used with frames. Most cases: set to ""
         (empty). If you have frames in the template set to "page".



.. _constants-content:

content
"""""""


.. _constants-defaultheadertype:

defaultHeaderType
~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         defaultHeaderType

   Data type
         int

   Description
         The number of the header layout to be used by default

   Default
         1



.. _constants-pageframeobj:

pageFrameObj
~~~~~~~~~~~~

.. container:: table-row

   Property
         pageFrameObj

   Data type
         string

   Description
         The name of the "contentframe". Normally set to "page" if the site has
         a frameset. Otherwise it should be an empty value. This is important,
         as it determines the target of internal links!



.. _constants-shortcut-tables:

shortcut.tables
~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         shortcut.tables

   Data type
         string

   Description
         List of tables with an old-style name

   Default
         tt_content,tt_address,tt_links,tt_guest,tt_board,tt_calender,tt_products,tt_news,tt_rating,tt_poll



.. _constants-spacebefore:

spaceBefore
~~~~~~~~~~~

.. container:: table-row

   Property
         spaceBefore

   Data type
         int

   Description
         Space before each content element (pixels)

   Default
         0



.. _constants-spaceafter:

spaceAfter
~~~~~~~~~~

.. container:: table-row

   Property
         spaceAfter

   Data type
         int

   Description
         Space after each content element (pixels)

   Default
         0


.. _constants-styles-content:

styles.content
""""""""""""""


.. _constants-styles-content-getnews-newspid:

getNews.newsPid
~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         getNews.newsPid

   Data type
         int

   Description
         If your template has a column with "news"-content fetched from another
         page-id, this is where you enter the id-number of that page!

   Default
         0


.. _constants-styles-content-links:

styles.content.links
""""""""""""""""""""


.. _constants-styles-content-links-exttarget:

extTarget
~~~~~~~~~

.. container:: table-row

   Property
         extTarget

   Data type
         string

   Description
         Target for external links

   Default
         \_blank



.. _constants-styles-content-links-target:

target
~~~~~~

.. container:: table-row

   Property
         target

   Data type
         string

   Description
         Default target for links

   Default
         {$PAGE\_TARGET}



.. _constants-styles-content-links-allowtags:

allowTags
~~~~~~~~~

.. container:: table-row

   Property
         allowTags

   Data type
         string

   Description
         Tags allowed in RTE content.


   Default
         b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blo
         ckquote,strike,del,ins,span,h1,h2,h3,h4,h5,h6,address


.. _constants-styles-content-imgtext:

styles.content.imgtext
""""""""""""""""""""""


.. _constants-styles-content-imgtext-maxw:

maxW
~~~~

.. container:: table-row

   Property
         maxW

   Data type
         int

   Description
         This indicates that maximum number of pixels (width) a block of images
         inserted as content is allowed to consume.

   Default
         600



.. _constants-styles-content-imgtext-maxwintext:

maxWInText
~~~~~~~~~~

.. container:: table-row

   Property
         maxWInText

   Data type
         int

   Description
         Same as above, but this is the maximum width when text is wrapped
         around an imageblock. Default is 50% of the normal Max Image Width.



.. _constants-styles-content-imgtext-captionsplit:

captionSplit
~~~~~~~~~~~~

.. container:: table-row

   Property
         captionSplit

   Data type
         bool

   Description
         **Deprecated** Use :code:`imageTextSplit` below instead


   Default
         0



.. _constants-styles-content-imgtext-imagetextsplit:

imageTextSplit
~~~~~~~~~~~~~~

.. container:: table-row

   Property
         imageTextSplit

   Data type
         bool

   Description
         If this is set, then the image text (caption, alt, title, longdesc)
         will be split by each line and they will appear on the corresponding
         images in the image list.

   Default
         1



.. _constants-styles-content-imgtext-emptytitlehandling:

emptyTitleHandling
~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         emptyTitleHandling

   Data type
         string

   Description
         How the 'title' attribute will be handled if no title is given for an
         image. Possible choices: "keepEmpty", "useAlt" or "removeAttr".
         Recommended for accessibility is "removeAttr". For correct tooltips on
         IE, use "keepEmpty". For use of alt="" text as title use "useAlt".

   Default
         removeAttr



.. _constants-styles-content-imgtext-titleinlink:

titleInLink
~~~~~~~~~~~

.. container:: table-row

   Property
         titleInLink

   Data type
         bool

   Description
         Do you want the 'title' attribute to be added to the surrounding <a>
         tag, if present? Recommended for accessibility is "true".

   Default
         1



.. _constants-styles-content-imgtext-titleinlinkandimg:

titleInLinkAndImg
~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         titleInLinkAndImg

   Data type
         bool

   Description
         If you have the title in the <a>-tag (titleInLink=1), you don't get
         the 'title' in the <img>-tag. IE6 will not show the tooltip anymore.
         So to get the 'title' in <img> too (to keep IE happy), set this too.
         Recommended for accessibility is "false". For correct tooltips on IE6,
         set this to "true".

   Default
         0



.. _constants-styles-content-imgtext-colspace:

colSpace
~~~~~~~~

.. container:: table-row

   Property
         colSpace

   Data type
         int

   Description
         Horizontal distance between images in content elements of type "Images"
         or "Text & Images". If you change this manually in your CSS, you need
         to adjust this setting accordingly.

   Default
         10



.. _constants-styles-content-imgtext-rowspace:

rowSpace
~~~~~~~~

.. container:: table-row

   Property
         rowSpace

   Data type
         int

   Description
         Vertical distance after image rows in content elements of type "Images"
         or "Text & Images". If you change this manually in your CSS, you need
         to adjust this setting accordingly.

   Default
         5



.. _constants-styles-content-imgtext-textmargin:

textMargin
~~~~~~~~~~

.. container:: table-row

   Property
         textMargin

   Data type
         int

   Description
         Horizontal distance between images and text in content elements of
         type "Text & Images".

   Default
         10



.. _constants-styles-content-imgtext-bordercolor:

borderColor
~~~~~~~~~~~

.. container:: table-row

   Property
         borderColor

   Data type
         string

   Description
         Border color of images in content elements when "Border"-option for
         element is set. Has to be either a defined color (like black, lime,
         maroon) or a hexadecimal color code (like :code:`#FF00FF`)

   Default
         black



.. _constants-styles-content-imgtext-borderthick:

borderThick
~~~~~~~~~~~

.. container:: table-row

   Property
         borderThick

   Data type
         int

   Description
         Thickness (in pixels) of border around images in content elements when
         "Border"-option for element is set.

   Default
         2



.. _constants-styles-content-imgtext-borderspace:

borderSpace
~~~~~~~~~~~

.. container:: table-row

   Property
         borderSpace

   Data type
         int

   Description
         Padding (in pixels) left and right to the image, around the border.

   Default
         0



.. _constants-styles-content-imgtext-borderselector:

borderSelector
~~~~~~~~~~~~~~

.. container:: table-row

   Property
         borderSelector

   Data type
         string

   Description
         The selector where the image border is applied to. If you want your
         border to apply elsewhere, change this setting. E.g. to apply to the
         whole image+caption, use 'DIV.csc-textpic-border DIV.csc-textpic-
         imagewrap .csc-textpic-image'.

   Default
         DIV.{$styles.content.imgtext.borderClass} DIV.csc-textpic-imagewrap
         .csc-textpic-image IMG, DIV.{$styles.content.imgtext.borderClass} DIV
         .csc-textpic-single-image IMG



.. _constants-styles-content-imgtext-borderclass:

borderClass
~~~~~~~~~~~

.. container:: table-row

   Property
         borderClass

   Data type
         string

   Description
         The name of the CSS class inserted and used for creating image borders

   Default
         csc-textpic-border



.. _constants-styles-content-imgtext-separaterows:

separateRows
~~~~~~~~~~~~

.. container:: table-row

   Property
         separateRows

   Data type
         bool

   Description
         Whether images should be rendered/wrapped in separated rows, e.g.
         inside a DIV.csc-textpic-imagerow element

   Default
         1



.. _constants-styles-content-imgtext-linkwrap:

styles.content.imgtext.linkWrap
"""""""""""""""""""""""""""""""


.. _constants-styles-content-imgtext-linkwrap-width:

width
~~~~~

.. container:: table-row

   Property
         width

   Data type
         int+

   Description
         This specifies the width of the enlarged image when click-enlarge is
         enabled.

   Default
         800m



.. _constants-styles-content-imgtext-linkwrap-height:

height
~~~~~~

.. container:: table-row

   Property
         height

   Data type
         int+

   Description
         This specifies the height of the enlarged image when click-enlarge is
         enabled.

   Default
         600m



.. _constants-styles-content-imgtext-linkwrap-effects:

effects
~~~~~~~

.. container:: table-row

   Property
         effects

   Data type
         string

   Description
         Effects applied to the enlarged image.



.. _constants-styles-content-imgtext-linkwrap-newwindow:

newWindow
~~~~~~~~~

.. container:: table-row

   Property
         newWindow

   Data type
         bool

   Description
         If set, every click-enlarged image will open in it's own popup window
         and not the current popup window (which may have a wrong size for the
         image to fit in)

   Default
         0



.. _constants-styles-content-imgtext-linkwrap-lightboxenabled:

lightboxEnabled
~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         lightboxEnabled

   Data type
         string

   Description
         If set, images will be rendered with a link to their big version and a
         specified css class and rel attribute to easily allow the use of
         lightboxes

   Default
         0



.. _constants-styles-content-imgtext-linkwrap-lightboxcssclass:

lightboxCssClass
~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         lightboxCssClass

   Data type
         string

   Description
         Which CSS class to use for lightbox links (only applicable if lightbox
         rendering is enabled)

   Default
         lightbox



.. _constants-styles-content-imgtext-linkwrap-lightboxrelattribute:

LightboxRelAttribute
~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         LightboxRelAttribute

   Data type
         string

   Description
         Which rel="" attribute to use for lightbox links (only applicable if
         lightbox rendering is enabled)

   Default
         lightbox[{field:uid}]


.. _constants-styles-content-uploads:

styles.content.uploads
""""""""""""""""""""""


.. _constants-styles-content-uploads-jumpurl-secure:

jumpurl\_secure
~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         jumpurl\_secure

   Data type
         bool

   Description
         Set to 1 to secure "jump URLs".



.. _constants-styles-content-uploads-jumpurl-secure-mimetypes:

jumpurl\_secure\_mimeTypes
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         jumpurl\_secure\_mimeTypes

   Data type
         string

   Description
         Comma-separated list of mime types for which "jump URLs" should be secured.

   Default
         pdf=application/pdf, doc=application/msword



.. _constants-styles-content-uploads-jumpurl:

jumpurl
~~~~~~~

.. container:: table-row

   Property
         jumpurl

   Data type
         bool

   Description
         Set to 1 to active "jump URLs".



.. _constants-styles-content-uploads-filesizebytelabels:

filesizeByteLabels
~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         filesizeByteLabels

   Data type
         string

   Description
         The labels for bytes, kilobytes, megabytes and gigabytes

   Default
         " \| K\| M\| G"


.. _constants-styles-content-mailform:

styles.content.mailform
"""""""""""""""""""""""


.. _constants-styles-content-mailform-target:

target
~~~~~~

.. container:: table-row

   Property
         target

   Data type
         string

   Description
         The mailform target

   Default
         {$PAGE\_TARGET}



.. _constants-styles-content-mailform-goodmess:

goodMess
~~~~~~~~

.. container:: table-row

   Property
         goodMess

   Data type
         string

   Description
         This is the message (if any) that is popped-up (JavaScript) when a
         user clicks "send" in an email-form



.. _constants-styles-content-mailform-badmess:

badMess
~~~~~~~

.. container:: table-row

   Property
         badMess

   Data type
         string

   Description
         This is the message that is popped-up (JavaScript) when a user has NOT
         filled required fields in an email-form


.. _constants-styles-content-loginform:

styles.content.loginform
""""""""""""""""""""""""


.. _constants-styles-content-loginform-target:

target
~~~~~~

.. container:: table-row

   Property
         target

   Data type
         string

   Description
         The login form target

   Default
         \_top



.. _constants-styles-content-loginform-goodmess:

goodMess
~~~~~~~~

.. container:: table-row

   Property
         goodMess

   Data type
         string

   Description
         This is the message (if any) that is popped-up (JavaScript) when a
         user logs in as a front-end user


.. _constants-styles-content-loginform-pid:

pid
~~~

.. container:: table-row

   Property
         pid

   Data type
         int

   Description
         Enter the page-uid number (PID) of the sysFolder where you keep your
         fe\_users that are supposed to login on this site. This setting is
         necessary, if login is going to work (and you aren't using "felogin")!


.. _constants-styles-content-searchform:

styles.content.searchform
"""""""""""""""""""""""""


.. _constants-styles-content-searchform-goodmess:

goodMess
~~~~~~~~

.. container:: table-row

   Property
         goodMess

   Data type
         string

   Description
         This is the message (if any) that is popped-up (JavaScript) when a
         user performs a search


.. _constants-styles-content-searchresult:

styles.content.searchresult
"""""""""""""""""""""""""""


.. _constants-styles-content-searchresult-resulttarget:

resultTarget
~~~~~~~~~~~~

.. container:: table-row

   Property
         resultTarget

   Data type
         string

   Description
         Search result links target.


   Default
         {$PAGE\_TARGET}



.. _constants-styles-content-searchresult-target:

target
~~~~~~

.. container:: table-row

   Property
         target

   Data type
         string

   Description
         Target for the search results pagination links.


   Default
         {$PAGE\_TARGET}


.. _constants-styles-content-media:

styles.content.media
""""""""""""""""""""


.. _constants-styles-content-media-videoplayer:

videoPlayer
~~~~~~~~~~~

.. container:: table-row

   Property
         videoPlayer

   Data type
         string

   Description
         configure the path to the video player

   Default
         EXT:mediace/Resources/Contrib/flashmedia/flvplayer.swf



.. _constants-styles-content-media-defaultvideowidth:

defaultVideoWidth
~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         defaultVideoWidth

   Data type
         int

   Description
         define the default width for the media video (in pixels)

   Default
         600



.. _constants-styles-content-media-defaultvideoheight:

defaultVideoHeight
~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         defaultVideoHeight

   Data type
         int

   Description
         define the default height for the media video (in pixels)

   Default
         400



.. _constants-styles-content-media-audioplayer:

audioPlayer
~~~~~~~~~~~

.. container:: table-row

   Property
         audioPlayer

   Data type
         string

   Description
         configure the path to the audio player

   Default
         EXT:mediace/Resources/Contrib/flashmedia/player.swf



.. _constants-styles-content-media-defaultaudiowidth:

defaultAudioWidth
~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         defaultAudioWidth

   Data type
         int

   Description
         define the default width for the media audio (in pixels)

   Default
         300



.. _constants-styles-content-media-defaultaudioheight:

defaultAudioHeight
~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         defaultAudioHeight

   Data type
         int

   Description
         define the default height for the media audio (in pixels)

   Default
         30

