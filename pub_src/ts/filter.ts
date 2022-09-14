import { capitalize, objCmp, SortFun } from "./common";
import { ColumnIndices } from "./db";
import { Tag } from "./tag";

export abstract class Filter {
    protected static readonly FORM_NAME = '-form-filter';

    public key: string;
    protected title: string;
    protected toLabel: (v: string) => string;
    protected sortFun: SortFun<string>;
    protected handler: (e: Event) => any;
    protected formName: string;
    protected itemName: string;
    protected index: number;

    public constructor(
        key: string,
        title?: string,
        toLabel?: (v: any) => string,
        sortFun?: SortFun<string>
    ) {
        this.key = key;
        this.title = title ? title : capitalize(key) + ' Filter';
        this.toLabel = toLabel ? toLabel : (v) => v;
        this.sortFun = sortFun ? sortFun : (a, b) => objCmp(this.toLabel(a), this.toLabel(b));
    }

    protected getValuesCount(data: any[][]) {
        const values: { [index: string]: number } = {};
        for (const d of data) {
            const v = d[this.index];
            if (!values[v]) {
                values[v] = 0;
            }
            values[v]++;
        }
        return values;
    }

    public render(data: any[][], ci: ColumnIndices, handler: (e: Event) => any): Tag<HTMLFormElement> {
        this.formName = Filter.FORM_NAME + '-' + this.key;
        this.itemName = this.formName + '-item';
        this.handler = handler;
        this.index = ci[this.key];
        const fieldSet = Tag.fieldset(this.title).cls('checkbox');
        const values = this.getValuesCount(data);
        const keys = Object.keys(values).sort(this.sortFun);
        const checks = this.createChecks(values, keys);
        for (const check of checks) {
            check.putInto(fieldSet);
        }
        return Tag.of<HTMLFormElement>('form').name(this.formName).add(fieldSet);
    }

    protected checkbox(
        name: string,
        type: 'radio' | 'checkbox',
        value: any,
        count: number,
        checked = false
    ) {
        const input = Tag.of('input')
            .name(name)
            .attr({ value: value, type: type })
            .event('change', this.handler.bind(this));
        if (checked) {
            input.attr({ checked: 'checked' });
        }
        return Tag.span(
            input,
            Tag.of('label').add(this.toLabel(value)),
            Tag.span('(' + count + ')').cls('hot'),
        );
    }

    protected abstract createChecks(values: { [index: string]: number }, keys: string[]): Tag<HTMLElement>[];

    protected abstract testFunc(): (data: any[]) => boolean;

    public filter(data: any[][]): any[][] {
        const test = this.testFunc();
        return data.filter(test);
    }
}

export class RadioFilter extends Filter {
    protected createChecks(values: { [index: string]: number; }, keys: string[]): Tag<HTMLElement>[] {
        const checks: Array<Tag<HTMLElement>> = [];
        checks.push(this.checkbox(
            this.itemName,
            'radio',
            'All',
            Object.values(values).reduce((a: number, b: number) => a + b, 0),
            true
        ));
        for (const v of keys) {
            checks.push(this.checkbox(this.itemName, 'radio', v, values[v], false));
        }
        return checks;
    }

    protected testFunc() {
        const items = document.forms.namedItem(this.formName)?.elements.namedItem(this.itemName);
        if (items instanceof RadioNodeList) {
            // RadioNodeList support 'value' for radio buttons.
            return (d: any[]) => items.value == 'All' || items.value == d[this.index];
        }
        return (_d: any[]) => true;
    }
}

export class CheckFilter extends Filter {
    protected createChecks(values: { [index: string]: number; }, keys: string[]): Tag<HTMLElement>[] {
        return keys.map((v, i) => this.checkbox(this.itemName, 'checkbox', v, values[v], true));
    }

    testFunc() {
        const items = document.forms.namedItem(this.formName)?.elements.namedItem(this.itemName);
        if (items instanceof RadioNodeList) {
            return (data: any[]) => {
                for (let i = 0; i < items.length; ++i) {
                    const c = items.item(i) as HTMLInputElement;
                    if (c.value == data[this.index] && c.checked) {
                        return true;
                    }
                }
                return false;
            }
        }
        return (_d: any[]) => true;
    }
}

export class MultiCheckFilter extends Filter {
    protected createChecks(values: { [index: string]: number; }, keys: string[]): Tag<HTMLElement>[] {
        return keys.map((v, i) => this.checkbox(this.itemName, 'checkbox', v, values[v], false));
    }

    protected getValuesCount(data: any[][]) {
        const values: { [index: string]: number } = {};
        for (const d of data) {
            const vs = d[this.index];
            for (const v of vs) {
                if (!values[v]) {
                    values[v] = 0;
                }
                values[v]++;
            }
        }
        return values;
    }

    testFunc() {
        const items = document.forms.namedItem(this.formName)?.elements.namedItem(this.itemName);
        if (items instanceof RadioNodeList) {
            return (data: any[]) => {
                for (let i = 0; i < items.length; ++i) {
                    const c = items.item(i) as HTMLInputElement;
                    const v = parseInt(c.value);
                    if (c.checked && !data[this.index].includes(isNaN(v) ? c.value : v)) {
                        return false;
                    }
                }
                return true;
            }
        }
        return (_d: any[]) => true;
    }
}
