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
	}
	return false;
}
