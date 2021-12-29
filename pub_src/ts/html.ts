export function onLoad(fun: (this: Window, ev: Event) => any) {
    window.addEventListener('load', fun);
}
