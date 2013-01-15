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
