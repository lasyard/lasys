export type SortFun<T> = (a: T, b: T) => -1 | 0 | 1;

export function rand(x: number) {
    return Math.floor(Math.random() * x);
}

export function objCmp(a: any, b: any) {
    return (a > b) ? 1 : (a == b) ? 0 : -1;
}

export function numCmp(a: string, b: string) {
    return objCmp(parseInt(a), parseInt(b));
}

/**
 * Convert a zh string to an integer, limit to < 100.
 *
 * @param str the string.
 */
export function zhToNum(str: string) {
    const map: { [key: string]: number } = {
        '零': 0, '一': 1, '二': 2, '三': 3, '四': 4, '五': 5, '六': 6, '七': 7, '八': 8, '九': 9, '十': 10,
        '〇': 0, '壹': 1, '贰': 2, '叁': 3, '肆': 4, '伍': 5, '陆': 6, '柒': 7, '捌': 8, '玖': 9, '拾': 10,
    };
    let result: number | null = null;
    for (let i = 0; i < str.length; ++i) {
        let n = map[str[i]];
        if (result == null) {
            result = n;
            if (result == 0) {
                break;
            }
        } else if (n == 10) {
            result *= n;
        } else if (n != undefined) {
            result += n;
        }
    }
    return result;
}

let collator: Intl.Collator | null = null;

export function zhCmp(a: string, b: string) {
    const regex = /[一二三四五六七八九十]/g;
    const a1 = a.replaceAll(regex, (m: string) => String(zhToNum(m)));
    const b1 = b.replaceAll(regex, (m: string) => String(zhToNum(m)));
    if (collator == null) {
        collator = new Intl.Collator('zh', { numeric: true });
    }
    return collator.compare(a1, b1);
}

export function dateCmp(a: string, b: string) {
    return objCmp(Date.parse(a), Date.parse(b));
}

export function capitalize(str: string) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

export function timeStr(time: string | number) {
    if (typeof time === 'string') {
        time = parseInt(time);
    }
    return new Date(time * 1000).toLocaleString([], {
        hour12: false,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

export function html(str: string) {
    return str
        .replace(/[<>&]/gm, (s: String) => "&#" + s.charCodeAt(0) + ";")
        .replace(/\r\n/gm, '<br />')
        .replace(/\n/gm, '<br />');
}

export function textBuilder() {
    let text = '';
    const p = (str: string) => {
        text += str + '\n';
    }
    p.text = () => text;
    return p;
}
