function tx_rsaauth_encrypt() {
	var rsa = new RSAKey();
	rsa.setPublic(document.loginform.n.value, document.loginform.e.value);

	var username = document.loginform.username.value;
	var password = document.loginform.p_field.value;

	var res = rsa.encrypt(password);

	// Remove all plaintext-data
	document.loginform.p_field.value = "";
	document.loginform.e.value = "";
	document.loginform.n.value = "";

	if (res) {
		document.loginform.userident.value = 'rsa:' + hex2b64(res);
	}
}

function tx_rsaauth_feencrypt(form) {
	// check if the form was already sent (see #40085)
	if (form.pass.value.match(/^rsa:/) || form.n.value == '' || form.e.value == '') {
		return;
	}
	var rsa = new RSAKey();
	rsa.setPublic(form.n.value, form.e.value);

	var username = form.user.value;
	var password = form.pass.value;

	var res = rsa.encrypt(password);

	// Remove all plaintext-data. This will also prevent plain text authentication.
	form.pass.value = "";
	form.e.value = "";
	form.n.value = "";

	if (res) {
		form.pass.value = 'rsa:' + hex2b64(res);
	}
}

function tx_rsaauth_feChangePasswordEncrypt(form) {
	var rsa = new RSAKey();
	rsa.setPublic(form.n.value, form.e.value);

	var elPassword1 = document.getElementById('tx_felogin_pi1-newpassword1');
	var elPassword2 = document.getElementById('tx_felogin_pi1-newpassword2');

	var password1 = elPassword1.value;
	var password2 = elPassword2.value;

	var res1 = rsa.encrypt(password1);
	var res2 = rsa.encrypt(password2);

	// Remove all plaintext-data. This will also prevent plain text authentication.
	elPassword1.value = "";
	elPassword2.value = "";
	form.e.value = "";
	form.n.value = "";

	if (res1 && res2) {
		elPassword1.value = 'rsa:' + hex2b64(res1);
		elPassword2.value = 'rsa:' + hex2b64(res2);
	}
}

function tx_rsaauth_encryptUserSetup() {

	var rsa = new RSAKey();
	rsa.setPublic(document.usersetup.n.value, document.usersetup.e.value);

	var password = document.getElementById('field_password').value;
	var password2 = document.getElementById('field_password2').value;

	if (password || password2) {
		var res = rsa.encrypt(password);
		var res2 = rsa.encrypt(password2);
		if (res && res2) {
			document.getElementById('field_password').value = 'rsa:' + hex2b64(res);
			document.getElementById('field_password2').value = 'rsa:' + hex2b64(res2);
		}
	}	return false;
}
