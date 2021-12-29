import sha256 from 'crypto-js/sha256';
import { Tag } from './tag';
import { onLoad } from './html';

onLoad(function () {
    Tag.form('login').event('submit', function (e: Event) {
        this.password.value = sha256(this.password.value);
    });
});
