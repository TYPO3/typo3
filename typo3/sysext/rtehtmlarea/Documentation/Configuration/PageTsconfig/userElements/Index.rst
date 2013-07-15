.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _userelements-configuration:

userElements:
"""""""""""""

Properties of each user elements setup.


.. _userelements-mode:

mode
~~~~

.. container:: table-row

   Property
         mode

   Data type
         string

   Description
         Which kind of object it is.

         Options:

         "wrap": If a wrap, then the content is exploded by "\|" and wrapped
         around the current text selection.

         "processor": The content is submitted to the php-script defined by
         .submitToScript. GPvar("processContent") carries the selection content
         of the RTE and GPvar("returnUrl") contains the return url. (The
         "content" property is not used here!)

         default: The content is just inserted (pasted into) at the cursor or
         substituting any current selection.



.. _userelements-description:

description
~~~~~~~~~~~

.. container:: table-row

   Property
         description

   Data type
         string

   Description
         A short description shown beneath the user element title (which is in
         bold)



.. _userelements-content:

content
~~~~~~~

.. container:: table-row

   Property
         content

   Data type
         string

   Description
         The content inserted/wrapped into the RTE



.. _userelements-submittoscript:

submitToScript
~~~~~~~~~~~~~~

.. container:: table-row

   Property
         submitToScript

   Data type
         string

   Description
         *(Applies only to mode=processor)*

         PHP script to which the current text selection of the RTE is
         submitted. The script must be relative to the site-url or a full url
         starting with http://...

         **Example:**

         ::

            .submitToScript = typo3/rte_cleaner.php

         or

         ::

            .submitToScript = http://www.domain.org/some_extenal_script.php



.. _userelements-dontinsertsiteurl:

dontInsertSiteUrl
~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         dontInsertSiteUrl

   Data type
         boolean

   Description
         If set, the marker ###\_URL### in the content property's content IS
         NOT substituted by the current site url. Normally you wish to do this
         for all image-references which must be prepended with the absolute url
         in order to display correctly in the RTE!


[page:->userElements]

