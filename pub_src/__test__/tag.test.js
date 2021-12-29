/**
 * @jest-environment jsdom
 */

import {
    Tag
} from '../ts/tag';

test('Tag', () => {
    const div = Tag.of('div').add('foo bar').id('id').cls('class').get();
    expect(div.tagName).toBe('DIV');
    // Why this is undefined?
    expect(div.innerText).toBe(undefined);
    expect(div.innerHTML).toBe('foo bar');
    expect(div.id).toBe('id');
    expect(div.className).toBe('class');
});
