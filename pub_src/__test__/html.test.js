/**
 * @jest-environment jsdom
 */

import {
    html,
    Tag
} from '../ts/html';

test('html("<a>") == "&#60;a&#62;"', () => {
    expect(html('<a>')).toBe('&#60;a&#62;');
});

test('Tag', () => {
    const div = Tag.of('div').add('foo bar').id('id').className('class').get();
    expect(div.tagName).toBe('DIV');
    // Why this is undefined?
    expect(div.innerText).toBe(undefined);
    expect(div.innerHTML).toBe('foo bar');
    expect(div.id).toBe('id');
    expect(div.className).toBe('class');
});
