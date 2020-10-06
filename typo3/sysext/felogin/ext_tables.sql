#
# Table structure for table 'fe_groups'
#
CREATE TABLE fe_groups (
	felogin_redirectPid  tinytext 
);



#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	felogin_redirectPid  tinytext,
	felogin_forgotHash  varchar(80) default '' ,
	KEY felogin_forgotHash (felogin_forgotHash)
);


