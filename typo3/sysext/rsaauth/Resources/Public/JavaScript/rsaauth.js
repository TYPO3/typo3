function tx_rsaauth_encryptUserSetup() {

	var rsa = new RSAKey();
	rsa.setPublic(document.usersetup.n.value, document.usersetup.e.value);

	var password = document.getElementById('field_password').value;
	var password2 = document.getElementById('field_password2').value;
	var passwordCurrent = document.getElementById('field_passwordCurrent').value;

	if (password || password2 || passwordCurrent) {
		var res;
		if (res = rsa.encrypt(password)) {
			document.getElementById('field_password').value = 'rsa:' + hex2b64(res);
		}
		if (res = rsa.encrypt(password2)) {
			document.getElementById('field_password2').value = 'rsa:' + hex2b64(res);
		}
		if (res = rsa.encrypt(passwordCurrent)) {
			document.getElementById('field_passwordCurrent').value = 'rsa:' + hex2b64(res);
		}
	}
	return false;
}
