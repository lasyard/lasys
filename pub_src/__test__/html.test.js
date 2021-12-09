/**
 * @jest-environment jsdom
 */

import {
    html,
    newTag
} from '../html';

test('html("<a>") == "&#60;a&#62;"', () => {
    expect(html('<a>')).toBe('&#60;a&#62;');
});

test('newTag', () => {
    const div = newTag('div', 'foo bar', 'id', 'class');
    expect(div.tagName).toBe('DIV');
    // Why this is undefined?
    expect(div.innerText).toBe(undefined);
    expect(div.innerHTML).toBe('foo bar');
    expect(div.id).toBe('id');
    expect(div.className).toBe('class');
});
