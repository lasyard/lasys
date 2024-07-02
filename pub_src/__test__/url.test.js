/**
 * @jest-environment jsdom
 */

test('simple url', () => {
    const url = new URL('', location.href);
    expect(url.toString()).toBe('http://localhost/');
});

test('url construct', () => {
    const url = new URL('xiha', location.href);
    expect(url.toString()).toBe('http://localhost/xiha');
});

test('url with search params', () => {
    const url = new URL('', location.href);
    url.searchParams.set('type', 'foo');
    url.searchParams.set('name', 'bar')
    expect(url.toString()).toBe('http://localhost/?type=foo&name=bar');
});

test('url dup search params', () => {
    const url = new URL('?type=foo', location.href);
    expect(url.searchParams.get('type')).toBe('foo');
    url.searchParams.set('name', 'bar')
    expect(url.toString()).toBe('http://localhost/?type=foo&name=bar');
});
