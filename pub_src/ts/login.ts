import sha256 from 'crypto-js/sha256';
import { Tag } from './tag';
import { onLoad } from './html';

onLoad(() => {
    // Here `function` is used, for right `this` reference.
    Tag.form('login')?.event('submit', function (this: HTMLFormElement, e: Event) {
        this.password.value = sha256(this.password.value);
    });
});
