import sha256 from 'crypto-js/sha256';
import { onLoad } from './html';

onLoad(function () {
    document.forms.namedItem('login').addEventListener('submit', function (e: Event) {
        this.password.value = sha256(this.password.value);
    });
});
