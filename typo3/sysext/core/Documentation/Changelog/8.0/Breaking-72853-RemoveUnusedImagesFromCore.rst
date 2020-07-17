
.. include:: ../../Includes.txt

=================================================
Breaking: #72853 - Remove unused Images from core
=================================================

See :issue:`72853`

Description
===========

A lot of unused images from the core have been removed.
Although it is not a good style, some extensions use references to one or more files.


Impact
======

References to the images listed below can throw a 404 not found.

Deleted images:

.. code-block:: none

   typo3/sysext/backend/Resources/Public/Images/Overlay/default.gif
   typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_deleted.gif
   typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_group.gif
   typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_hidden.gif
   typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_hidden_timing.gif
   typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_no_icon_found.gif
   typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_sub5.gif
   typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_timing.gif
   typo3/sysext/filemetadata/Resources/Public/Icons/status_1.png
   typo3/sysext/filemetadata/Resources/Public/Icons/status_2.png
   typo3/sysext/filemetadata/Resources/Public/Icons/status_3.png
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/bullet_list.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/div.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/filelinks.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/html.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/images_only.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/login_form.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/mailform.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/multimedia.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/regular_header.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/regular_text.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/searchform.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/shortcut.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/sitemap.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/sitemap2.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/table.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/text_image_below.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/text_image_right.gif
   typo3/sysext/frontend/Resources/Public/Icons/ContentElementWizard/user_defined.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/above_center.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/above_left.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/above_right.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/below_center.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/below_left.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/below_right.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/intext_left.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/intext_left_nowrap.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/intext_right.gif
   typo3/sysext/frontend/Resources/Public/Icons/ImageOrientation/intext_right_nowrap.gif
   typo3/sysext/frontend/Resources/Public/Images/wizard_backend_layout.png
   typo3/sysext/opendocs/Resources/Public/Icons/opendocs.png
   typo3/sysext/opendocs/Resources/Public/Images/toolbar_item_active_bg.png
   typo3/sysext/tstemplate/Resources/Public/gfx/BUG_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/BUG_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/BUG_menu2.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/BUSINESS_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/BUSINESS_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/CANDIDATE_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/CANDIDATE_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/CANDIDATE_page.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/CrCPH_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/FIRST_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/FIRST_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/GLCK_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/GLCK_columns.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/GLCK_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/GREEN_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/GREEN_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/GREEN_menu2.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/GREEN_menu3.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/HYPER_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/HYPER_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/HYPER_menu2.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/HYPER_page.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/HYPER_toptitle.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/MM_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/MM_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/MM_right.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/NEWSLETTER_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/RE_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/RE_leftmenu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/RE_menu.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/RE_top.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/TU_basic.gif
   typo3/sysext/tstemplate/Resources/Public/gfx/TU_menu.gif


Affected Installations
======================

Installations or extensions which have references to this images.


Migration
=========

No migration

.. index:: Frontend, Backend
