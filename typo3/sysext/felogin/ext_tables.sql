CREATE TABLE fe_users (
	# type=passthrough needs manual configuration
	felogin_forgotHash varchar(160) default '' ,
	KEY felogin_forgotHash (felogin_forgotHash)
);
