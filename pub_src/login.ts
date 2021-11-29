import 'crypto-js/core'
import 'crypto-js/sha256';

window.addEventListener('load', function () {
    document.forms.namedItem('login').addEventListener('submit', function (e: Event) {
        this.password.value = CryptoJS.SHA256(this.password.value);
    });
});
