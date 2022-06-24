export function onContentLoad(fun: () => any) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fun);
    } else {
        fun();
    }
}

export function onLoad(fun: () => any) {
    window.addEventListener('load', fun);
}
