<?php
/**
* Default  TCA_DESCR for "be_groups"
*/

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'This is the backend administration user groups available for the Backend users. These determine the permissions for the Backend users.',
		'_.seeAlso' => 'be_users',
		'title.description' => 'Name of the Backend usergroup',
		'db_mountpoints.description' => 'Assign startpoints for the users page tree.',
		'db_mountpoints.details' => 'The page tree used my all Web-submodules to navigate must have some points-of-entry defined. Here you should insert one or more references to a page which will represent a new root page for the page tree. This is called a \'Database mount\'.
DB mounts may be inherited by the users which are members of this group. This does depend on whether the user is configured to include the mounts set in the member groups. However it\'s recommended to use backend user groups like this to configure mounts. Especially if the need to be shared amoung many users.',
		'_db_mountpoints.seeAlso' => 'be_groups:file_mountpoints,
be_users:db_mountpoints,
be_users:options
',
		'file_mountpoints.description' => 'Assign startpoints for the file folder tree.',
		'file_mountpoints.details' => 'The file folder tree is used by all File-submodules to navigate between the file folders on the webserver.
Notice as with \'DB mounts\' the file folder mounts may be inherited by the users which are members of this group.',
		'_file_mountpoints.seeAlso' => 'be_groups:db_mountpoints,
be_users:file_mountpoints,
be_users:options
',
		'pagetypes_select.description' => 'Select which \'Types\' of Pages the members may set.',
		'pagetypes_select.details' => 'This option limits the number of valid choices for the user when he is about to select a page type.',
		'_pagetypes_select.seeAlso' => 'pages:doktype,
be_groups:inc_access_lists',
		'tables_modify.description' => 'Select which tables the members may modify.',
		'tables_modify.details' => 'An important part of setting permissions is to define which database tables a user is allowed to modify. 
Tables allowed for modification is automatically also allowed for selection and thus you don\'t need to set tables entered here in the "Tables (listing)" box.

<strong>Notice</strong> that this list adds to the fields selected in other member groups of a user.',
		'_tables_modify.seeAlso' => 'be_groups:tables_select,
be_groups:inc_access_lists',
		'tables_select.description' => 'Select which tables the members may see in record lists (\'modify\' tables does not need to be re-entered here!).',
		'tables_select.details' => 'This determines which tables - in addition to those selected in the "Tables (modify)" box - may be viewed and listed for the user. He is thus not able to <em>edit</em> the table - only select the records and view the content.
This list is not so very important. It\'s a pretty rare situation that a user may select tables but not modify them.',
		'_tables_select.seeAlso' => 'be_groups:tables_modify,
be_groups:inc_access_lists',
		'non_exclude_fields.description' => 'Certain table fields are not available by default. Those fields can be explicitly enabled for the group members here.',
		'non_exclude_fields.details' => '"Allowed excludefields" allows you to detail the permissions granted to tables. By default all these fields are not available to users but must be specifically enabled by being selected here.
One application of this is that pages are usually hidden by default and that the hidden field is not available for a user unless he has been granted access by this list of "Allowed excludefields". So the user may create a new page, but cannot un-hide the page. Unless of course he has been assigned the "Page: Hidden" exclude field through one of his member groups.
Of course it does not make any sense to add fields from tables which are not included in the list of table allowed to be modified.',
		'_non_exclude_fields.seeAlso' => 'be_groups:inc_access_lists',
		'hidden.description' => 'Disables a user group.',
		'hidden.details' => 'If you disable a user group all user which are members of the group will in effect not inherit any properties this group may have given them.',
		'lockToDomain.description' => 'Enter the host name from which the user is forced to login.',
		'lockToDomain.details' => 'A TYPO3 system may have multiple domains pointing to it. Therefore this option secures that users can login only from a certain host name.',
		'_lockToDomain.seeAlso' => 'be_users:lockToDomain,
fe_users:lockToDomain,
fe_groups:lockToDomain',
		'groupMods.description' => 'Select available backend modules for the group members.',
		'groupMods.details' => 'This determines which \'menu items\' are available for the group members.
This list of modules is added to any modules selected in other member groups of a user as well as the corresponding field of the user himself.',
		'_groupMods.seeAlso' => 'be_users:userMods,
be_groups:inc_access_lists',
		'inc_access_lists.description' => 'Select whether Page type, Table, Module and Allowed excludefield access lists are enabled for this group.',
		'_inc_access_lists.seeAlso' => 'be_groups:pagetypes_select,
be_groups:tables_modify,
be_groups:tables_select,
be_groups:groupMods,
be_groups:non_exclude_fields
',
		'description.description' => 'Enter a short description of the user group, what it is for and who should be members. This is for internal use only.',
		'_description.seeAlso' => 'fe_groups:description',
		'TSconfig.description' => 'Additional configuration through TypoScript style values (Advanced).',
		'TSconfig.syntax' => 'TypoScript style without conditions and constants.',
		'_TSconfig.seeAlso' => 'be_users:TSconfig
fe_users:TSconfig
fe_groups:TSconfig
pages:TSconfig',
		'hide_in_lists.description' => 'This option will prevent the user group from showing up in lists, where user groups are selected.',
		'hide_in_lists.details' => 'This will affect the list of user groups in the Task Center To-Do and Messages parts as well as the Web>Access module.
The option is extremely useful if you have general user groups defining some global properties which all your users are members of. Then you would probably not like all those users to \'see\' each other through the membership of this group, for instance sending messages or To-Dos to each other. And this is what is option will prevent.',
		'subgroup.description' => 'Select backend user groups which are automatically included for members of this group.',
		'subgroup.details' => 'The properties or subgroups are added to the properties of this groups and basically they will simply be added to the list of member groups of any user which is a member of this group.
This feature provides a great way to create \'Supervisor\' user groups.',
	),
);
?>