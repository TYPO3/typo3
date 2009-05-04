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
