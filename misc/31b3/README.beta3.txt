In beta3 you must redefine you values in typo3conf/localconf.php

A lot of global variables has been substituted with a more organized approach to configuration.

This means that the previous global vars are substituted with values in an array, just like you see below here:


[PREVIOUS GLOBAL-VAR]  = [CURRENT VALUE]

## Image Manipulation:

PATH_ImageMagick		= $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["im_path"]
PATH_ImageMagick_LWZ	= $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["im_path_lzw"]
IMAGE_FILE_EXT			= $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"]
NEGATE_MASK_ImageMagick	= $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["im_negate_mask"]
MASK_TEMP_EXT_GIF		= $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["im_mask_temp_ext_gif"]
IM_VERSION_5			= $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["im_version_5"]
NO_IM_EFFECTS			= $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["im_no_effects"]

## (negated values)
NO_IMAGE_MAGICK			= !$GLOBALS["TYPO3_CONF_VARS"]["GFX"]["im"]
NO_GBLIB				= !$GLOBALS["TYPO3_CONF_VARS"]["GFX"]["gdlib"]
NO_IMAGE_PROCESSING		= !$GLOBALS["TYPO3_CONF_VARS"]["GFX"]["image_processing"]
NO_THUMBS				= !$GLOBALS["TYPO3_CONF_VARS"]["GFX"]["thumbnails"]

## Others frontend:
GET_URL_ID_TOKEN		= $GLOBALS["TYPO3_CONF_VARS"]["FE"]["get_url_id_token"]
APACHE_LOGFILE_DIR		= $GLOBALS["TYPO3_CONF_VARS"]["FE"]["logfile_dir"]
DEBUG					= $GLOBALS["TYPO3_CONF_VARS"]["FE"]["debug"]

## Backend values
PATH_Unzip				= $GLOBALS["TYPO3_CONF_VARS"]["BE"]["unzip_path"]
WARNING_EMAIL_ADDR		= $GLOBALS["TYPO3_CONF_VARS"]["BE"]["warning_email_addr"]
CLEAR_DB_INFO			= $GLOBALS["TYPO3_CONF_VARS"]["BE"]["clear_db_info"]
REPORT_ERROR_HTML		= $GLOBALS["TYPO3_CONF_VARS"]["SYS"]["report_error_html"]


## (these are still global:)
DDMMYY					= $GLOBALS["TYPO3_CONF_VARS"]["SYS"]["ddmmyy"]
HHMM					= $GLOBALS["TYPO3_CONF_VARS"]["SYS"]["hhmm"]
sitename				= $GLOBALS["TYPO3_CONF_VARS"]["SYS"]["sitename"]
contentTable			= $GLOBALS["TYPO3_CONF_VARS"]["SYS"]["contentTable"]

fileadminDir			= $GLOBALS["TYPO3_CONF_VARS"]["BE"]["fileadminDir"]
lockRootPath			= $GLOBALS["TYPO3_CONF_VARS"]["BE"]["lockRootPath"]
userHomePath			= $GLOBALS["TYPO3_CONF_VARS"]["BE"]["userHomePath"]
groupHomePath			= $GLOBALS["TYPO3_CONF_VARS"]["BE"]["groupHomePath"]

