<?php
/**
* Default  TCA_DESCR for "sys_action"
*/

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'An \'Action\' is a configuration of a specific task which may be performed by assigned usergroups via the Task Center.',
		'.details' => 'Currently an Action may be configured to create additional backend usergroups without being and admin-user and by selecting from a limited set of options. Another option is to let an action initiate a SQL SELECT-query in the database and return the result as a CSV file.
Actions can be assigned to a Backend usergroup and they are activated by a single click in the Task Center.',
		'title.description' => 'Enter the title of the action. This is shown in the list of available actions in the Task Center.',
		'description.description' => 'Describe what the action does or allows to do.',
		'hidden.description' => 'Check this option if you wish to disable the availability of the action or non-admin users.',
		'hidden.details' => 'This option is a great way to disable an action during changes made to it because it still allows you as an \'Admin\'-user to activate it for test purposes.',
		'type.description' => 'Select the action type.',
		'type.details' => '<strong>"Create Backend User"</strong> allows to create backend users with a limited set of options. This action type is meant for semi-administrators among your users which is in charge of daily user administration - still without being full fledged \'Admin\'-users with \'a License to Kill everything\'.
When you select this option, you get to enter a \'template\' user, enter a prefix which the new users will automatically have and whether or not a user home-dir is created in the proces. Finally you can select a limited number of usergroups which the can be selected among.

<strong>"SQL-query"</strong> allows to make a fixed SQL SELECT-query in the database returned as CSV lists. When you have created an action of this type, you need to go to the Tools>DBint module and enter the Advanced Search feature. Here you can design your SQL-query. When you have designed it to select what you want correctly, you can select this action (by it\'s name) and save the query to the action there. From that point it will be effective from the Task Center. (Notice that the form of output selected in the Advanced Search function is also stored, so make sure to select CSV output there!)
',
		'assign_to_groups.description' => 'Select the backend users groups allowed to activate the action in the Task Center.',
		't1_userprefix.description' => 'Enter a prefix which is forcibly prepended to new usernames (eg. "news_")',
		't1_allowed_groups.description' => 'Enter the Backend user groups which the user performing the action is able to choose among (if any).',
		't1_create_user_dir.description' => 'If checked, a private home-directory is also created during user creation. ',
		't1_create_user_dir.details' => '<strong>Notice:</strong> $TYPO3_CONF_VARS["BE"]["userHomePath"] must be configured correctly along with $TYPO3_CONF_VARS["BE"]["lockRootPath"] and writable!',
		't1_copy_of_user.description' => 'Insert a current Backend user which will be used as a template for the new users created. ',
		't1_copy_of_user.details' => 'All values are copied to the new user, except username, password, name and email is of course overridden.
If the template user is a member of a group which is not defined among the "Groups which may be assigned through the action" that group is still set for the user and cannot be removed by the user carrying out the action.',
		't4_recordsToEdit.description' => '[FILL IN] sys_action->t4_recordsToEdit',
		't3_listPid.description' => '[FILL IN] sys_action->t3_listPid',
		't3_tables.description' => '[FILL IN] sys_action->t3_tables',

	),
	'dk' => Array (
	),
	'de' => Array (
	),
	'no' => Array (
	),
	'it' => Array (
	),
	'fr' => Array (
	),
	'es' => Array (
	),
	'nl' => Array (
	),
	'cz' => Array (
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
	),
	'tr' => Array (
	),
	'se' => Array (
	),
	'pt' => Array (
	),
	'ru' => Array (
	),
	'ro' => Array (
	),
	'ch' => Array (
	),
	'sk' => Array (
	),
	'lt' => Array (
	),
	'is' => Array (
	),
	'hr' => Array (
	),
	'hu' => Array (
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
	),
	'eu' => Array (
	),
	'bg' => Array (
	),
	'br' => Array (
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
	),
	'ua' => Array (
	),
);
?>