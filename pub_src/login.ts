import sha256 from 'crypto-js/sha256';

window.addEventListener('load', function () {
    document.forms.namedItem('login').addEventListener('submit', function (e: Event) {
        this.password.value = sha256(this.password.value);
    });
});
