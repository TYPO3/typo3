<?php

########################################################################
# Extension Manager/Repository config file for ext "blog_example".
#
# Auto generated 15-02-2011 19:01
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'A Blog Example for the Extbase Framework',
	'description' => 'An example extension demonstrating the features of the Extbase Framework. It is the back-ported and tweaked Blog Example package of FLOW3. Have fun playing with it!',
	'category' => 'example',
	'author' => 'TYPO3 core team',
	'author_company' => '',
	'author_email' => '',
	'shy' => '',
	'dependencies' => 'extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '1.4.0-devel',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.5.0-0.0.0',
			'extbase' => '1.3.0-0.0.0',
			'fluid' => '1.3.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:77:{s:16:"ext_autoload.php";s:4:"91f9";s:21:"ext_conf_template.txt";s:4:"9b9c";s:12:"ext_icon.gif";s:4:"e922";s:17:"ext_localconf.php";s:4:"e7f7";s:14:"ext_tables.php";s:4:"b3b3";s:14:"ext_tables.sql";s:4:"33b2";s:41:"Classes/Controller/AbstractController.php";s:4:"44c4";s:37:"Classes/Controller/BlogController.php";s:4:"4f84";s:40:"Classes/Controller/CommentController.php";s:4:"fbc0";s:37:"Classes/Controller/PostController.php";s:4:"c44f";s:38:"Classes/Domain/Model/Administrator.php";s:4:"67d5";s:29:"Classes/Domain/Model/Blog.php";s:4:"1c30";s:32:"Classes/Domain/Model/Comment.php";s:4:"a01a";s:31:"Classes/Domain/Model/Person.php";s:4:"7fac";s:29:"Classes/Domain/Model/Post.php";s:4:"1285";s:28:"Classes/Domain/Model/Tag.php";s:4:"c0e8";s:53:"Classes/Domain/Repository/AdministratorRepository.php";s:4:"dd2e";s:44:"Classes/Domain/Repository/BlogRepository.php";s:4:"b84b";s:46:"Classes/Domain/Repository/PersonRepository.php";s:4:"58b9";s:44:"Classes/Domain/Repository/PostRepository.php";s:4:"58a3";s:38:"Classes/Domain/Service/BlogFactory.php";s:4:"643c";s:42:"Classes/Domain/Validator/BlogValidator.php";s:4:"e8bb";s:42:"Classes/ViewHelpers/GravatarViewHelper.php";s:4:"662a";s:41:"Configuration/FlexForms/flexform_list.xml";s:4:"11b7";s:25:"Configuration/TCA/tca.php";s:4:"875b";s:38:"Configuration/TypoScript/constants.txt";s:4:"0b87";s:34:"Configuration/TypoScript/setup.txt";s:4:"11cd";s:48:"Configuration/TypoScript/DefaultStyles/setup.txt";s:4:"ece4";s:46:"Resources/Private/Backend/Layouts/Default.html";s:4:"8b03";s:50:"Resources/Private/Backend/Templates/Blog/Edit.html";s:4:"9011";s:51:"Resources/Private/Backend/Templates/Blog/Index.html";s:4:"f4ca";s:49:"Resources/Private/Backend/Templates/Blog/New.html";s:4:"e68c";s:50:"Resources/Private/Backend/Templates/Post/Edit.html";s:4:"905a";s:51:"Resources/Private/Backend/Templates/Post/Index.html";s:4:"2247";s:50:"Resources/Private/Backend/Templates/Post/Index.txt";s:4:"ac46";s:49:"Resources/Private/Backend/Templates/Post/New.html";s:4:"ed51";s:50:"Resources/Private/Backend/Templates/Post/Show.html";s:4:"ab24";s:40:"Resources/Private/Language/locallang.xml";s:4:"0618";s:44:"Resources/Private/Language/locallang_csh.xml";s:4:"5e95";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"e603";s:44:"Resources/Private/Language/locallang_mod.xml";s:4:"cd5d";s:38:"Resources/Private/Layouts/Default.html";s:4:"4ed3";s:40:"Resources/Private/Partials/BlogForm.html";s:4:"721f";s:43:"Resources/Private/Partials/CommentForm.html";s:4:"6606";s:42:"Resources/Private/Partials/FormErrors.html";s:4:"a141";s:40:"Resources/Private/Partials/PostForm.html";s:4:"04f6";s:44:"Resources/Private/Partials/PostMetaData.html";s:4:"9215";s:40:"Resources/Private/Partials/PostTags.html";s:4:"5d24";s:42:"Resources/Private/Templates/Blog/Edit.html";s:4:"8fab";s:43:"Resources/Private/Templates/Blog/Index.html";s:4:"69cc";s:41:"Resources/Private/Templates/Blog/New.html";s:4:"caaf";s:42:"Resources/Private/Templates/Post/Edit.html";s:4:"4dcf";s:43:"Resources/Private/Templates/Post/Index.html";s:4:"b81d";s:42:"Resources/Private/Templates/Post/Index.txt";s:4:"c3e6";s:41:"Resources/Private/Templates/Post/New.html";s:4:"9e3d";s:42:"Resources/Private/Templates/Post/Show.html";s:4:"bf64";s:36:"Resources/Public/Css/BlogExample.css";s:4:"9b2b";s:43:"Resources/Public/Icons/default_gravatar.gif";s:4:"6087";s:37:"Resources/Public/Icons/icon_close.gif";s:4:"31ee";s:38:"Resources/Public/Icons/icon_delete.gif";s:4:"90c6";s:36:"Resources/Public/Icons/icon_edit.gif";s:4:"3248";s:35:"Resources/Public/Icons/icon_new.gif";s:4:"5098";s:36:"Resources/Public/Icons/icon_next.gif";s:4:"6add";s:41:"Resources/Public/Icons/icon_plaintext.gif";s:4:"af96";s:40:"Resources/Public/Icons/icon_populate.gif";s:4:"500c";s:40:"Resources/Public/Icons/icon_previous.gif";s:4:"50c0";s:40:"Resources/Public/Icons/icon_relation.gif";s:4:"9e5c";s:64:"Resources/Public/Icons/icon_tx_blogexample_domain_model_blog.gif";s:4:"50a3";s:67:"Resources/Public/Icons/icon_tx_blogexample_domain_model_comment.gif";s:4:"53ba";s:66:"Resources/Public/Icons/icon_tx_blogexample_domain_model_person.gif";s:4:"0bef";s:64:"Resources/Public/Icons/icon_tx_blogexample_domain_model_post.gif";s:4:"cf99";s:63:"Resources/Public/Icons/icon_tx_blogexample_domain_model_tag.gif";s:4:"3731";s:46:"Resources/Public/Icons/FlashMessages/error.png";s:4:"1c8f";s:52:"Resources/Public/Icons/FlashMessages/information.png";s:4:"6235";s:47:"Resources/Public/Icons/FlashMessages/notice.png";s:4:"813d";s:43:"Resources/Public/Icons/FlashMessages/ok.png";s:4:"e36c";s:48:"Resources/Public/Icons/FlashMessages/warning.png";s:4:"dada";}',
	'suggests' => array(
	),
);

?>