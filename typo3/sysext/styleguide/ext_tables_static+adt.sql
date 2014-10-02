DROP TABLE IF EXISTS tx_styleguide_forms_staticdata;
CREATE TABLE tx_styleguide_forms_staticdata (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	value_1 tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

INSERT INTO tx_styleguide_forms_staticdata VALUES ('1', '0', 'foo');
INSERT INTO tx_styleguide_forms_staticdata VALUES ('2', '0', 'bar');
INSERT INTO tx_styleguide_forms_staticdata VALUES ('3', '0', 'foobar');
INSERT INTO tx_styleguide_forms_staticdata VALUES ('4', '0', 'foofoo');