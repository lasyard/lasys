import { SortFun } from './common';
import { Tag, TagContent } from './tag';
import { Tooltip } from './tooltip';
import { onLoad } from './html';
import { Mime, Ajax } from './ajax';
import { Filter } from './filter';
import { ValueCallback, StatObject } from './stat';

export type ColumnIndices = { [index: string]: number };

interface DataSet {
    columns: ColumnIndices;
    labels: string[];
    data: any[][];
}

type ContentFun = (d: ValueCallback) => TagContent;

interface ColumnDefinition {
    th?: string | ContentFun;
    td: string | ContentFun;
    width?: string;
    align?: string;
}

interface DbTableConfig {
    cols?: (ColumnDefinition | string)[];
    columns?: number;
    filters?: Filter[];
    pre?: (dataSet: DataSet) => void;
    group?: {
        key: string,
        title?: (k: string) => TagContent,
        sort?: SortFun<string>,
        nav?: string,
    };
    sort?: SortFun<ValueCallback>;
    stat?: ((count: number) => TagContent) | StatObject;
}

declare const _TABLE_FIELDS: { [index: string]: { primary: boolean, auto: boolean } };
declare const _TABLE_CAN_DELETE: boolean;

export class DbTable {
    private dataSet!: DataSet;
    private conf: DbTableConfig;
    private divFilters: Tag<HTMLDivElement> | null = null;
    private divData!: Tag<HTMLDivElement>;
    private formUpdate: Tag<HTMLFormElement> | null = null;
    private popupMsg: Tag<HTMLElement> | null = null;

    constructor(conf: DbTableConfig) {
        this.conf = conf;
    }

    private data(dataSet: DataSet) {
        this.dataSet = dataSet;
        const conf = this.conf;
        if (this.divFilters) {
            const div = this.divFilters;
            div.clear();
            if (conf.filters) {
                for (const filter of conf.filters) {
                    div.add(filter.render(dataSet.data, dataSet.columns, this.refresh.bind(this)));
                }
            }
        }
        if (!conf.cols) {
            conf.cols = Object.keys(dataSet.columns).map(
                c => ({ th: c, td: c } as ColumnDefinition)
            );
        }
        for (const i in conf.cols) {
            const v = conf.cols[i];
            if (typeof v === 'string') {
                conf.cols[i] = { th: v, td: v };
            }
        }
        return this;
    }

    private static clearFormData(form: HTMLFormElement) {
        for (let i = 0; i < form.elements.length; ++i) {
            const f = form.elements[i];
            if (f instanceof HTMLInputElement) {
                if (f.type === 'submit') {
                    continue;
                } else if (f.type === 'checkbox') {
                    f.checked = f.dataset.default?.toLowerCase() === 'true';
                } else {
                    f.value = f.dataset.default ?? '';
                }
            } else if (f instanceof HTMLTextAreaElement) {
                (f as HTMLTextAreaElement).value = f.dataset.default ?? '';
            } else if (f instanceof HTMLSelectElement) {
                (f as HTMLSelectElement).value = f.dataset.default ?? '';
            }
        }
    }

    private static setFormData(form: HTMLFormElement, d: (k: string) => any) {
        for (let i = 0; i < form.elements.length; ++i) {
            const f = form.elements[i];
            if (f instanceof HTMLInputElement) {
                if (f.type === 'submit') {
                    continue;
                } else if (f.type === 'checkbox') {
                    const found = f.name.match(/(.+)\[(\d+)\]/);
                    if (found) { // checkbox array
                        const n = found[1];
                        const k = parseInt(found[2]);
                        f.checked = d(n).includes(k);
                    } else {
                        f.checked = (d(f.name) == '1' ? true : false);
                    }
                } else {
                    f.value = d(f.name);
                }
            } else if (f instanceof HTMLTextAreaElement) {
                (f as HTMLTextAreaElement).value = d(f.name);
            } else if (f instanceof HTMLSelectElement) {
                (f as HTMLSelectElement).value = d(f.name);
            }
        }
    }

    private static retrieveFormData(form: HTMLFormElement) {
        const data: { [index: string]: any } = {};
        for (let i = 0; i < form.elements.length; ++i) {
            const f = form.elements[i];
            if (f instanceof HTMLInputElement) {
                if (f.type === 'submit') {
                    continue;
                } else if (f.type === 'checkbox') {
                    const found = f.name.match(/(.+)\[(\d+)\]/);
                    if (found) { // checkbox array
                        const n = found[1];
                        const k = parseInt(found[2]);
                        if (!data[n]) {
                            data[n] = [];
                        }
                        if ((f as HTMLInputElement).checked) {
                            data[n].push(k);
                        }
                    } else {
                        data[f.name] = (f as HTMLInputElement).checked ? 1 : 0;
                    }
                } else {
                    data[f.name] = (f.value !== '' ? f.value.trim() : null);
                }
            } else if (f instanceof HTMLTextAreaElement || f instanceof HTMLSelectElement) {
                data[f.name] = (f.value !== '' ? f.value.trim() : null);
            }
        }
        return data;
    }

    private showMsg(msg: string) {
        if (this.popupMsg) {
            this.popupMsg.html(msg).show();
        }
    }

    render(panelId: string) {
        const panel = Tag.byId(panelId);
        if (!panel) {
            return this;
        }
        this.popupMsg = Tag.byId('-popup-msg');
        this.popupMsg?.outClickHide();
        if (this.conf.filters) {
            this.divFilters = Tag.div().putInto(panel);
        }
        this.divData = Tag.divLoading().putInto(panel);
        const btnInsert = Tag.byId('-btn-insert');
        const divForm = Tag.byId('-div-form-insert');
        if (btnInsert && divForm) {
            btnInsert.clickShow(divForm);
            divForm.outClickHide();
        }
        const formInsert = Tag.form('-form-insert');
        if (formInsert) {
            const form = formInsert.get();
            const action = form.getAttribute('action');
            formInsert.event('submit', (e) => {
                const data = DbTable.retrieveFormData(form);
                Ajax.post(
                    (r) => {
                        DbTable.clearFormData(form);
                        divForm?.hide();
                        this.showMsg(r);
                        this.loadData();
                    },
                    JSON.stringify(data),
                    action ? action : '',
                    Mime.JSON,
                    Mime.HTML
                );
                e.preventDefault();
            });
            // set default value
            DbTable.clearFormData(form);
        }
        const formUpdate = Tag.form('-form-update');
        if (formUpdate) {
            const callback = (r: string) => {
                Tooltip.get().close();
                this.showMsg(r);
                this.loadData();
            };
            const form = formUpdate.get();
            // Do not use `form.action`, which may overrided by an input named `action`.
            const action = form.getAttribute('action');
            // Do this before updateForm vanished.
            Tag.byId('-span-insert-new')?.find('a')?.event('click', (e) => {
                const data = DbTable.retrieveFormData(form);
                for (const f in _TABLE_FIELDS) {
                    if (_TABLE_FIELDS[f].auto) {
                        delete data[f];
                    }
                }
                Ajax.post(
                    callback,
                    JSON.stringify(data),
                    action ? action : '',
                    Mime.JSON,
                    Mime.HTML
                );
                e.preventDefault();
            });
            formUpdate.vanish().show().event('submit', (e) => {
                const data = DbTable.retrieveFormData(form);
                const keys: { [index: string]: any } = {};
                for (const f in data) {
                    if (_TABLE_FIELDS[f].primary) {
                        keys[f] = data[f];
                        delete data[f];
                    }
                }
                Ajax.update(
                    callback,
                    JSON.stringify({ keys: keys, data: data }),
                    action ? action : '',
                    Mime.JSON,
                    Mime.HTML
                );
                e.preventDefault();
            });
            this.formUpdate = formUpdate;
        }
        return this;
    }

    private static addContent(t: Tag<HTMLElement>, data: any[], fun: string | ContentFun | undefined, ci: ColumnIndices) {
        let c: any;
        if (typeof fun === 'string') {
            c = data[ci[fun]];
        } else if (typeof fun === 'function') {
            c = fun((col) => data[ci[col]])
        }
        if (c != null) {
            t.add(c);
        }
    }

    private getStat(data: any[][], ci: ColumnIndices) {
        const stat = this.conf.stat;
        let result: TagContent = '';
        if (typeof stat === 'function') {
            result = stat(data.length);
        } else if (stat) {
            const ctx = stat.init();
            for (const d of data) {
                stat.fun((col) => d[ci[col]], ctx);
            }
            if (ctx instanceof Map) {
                ctx.set('__cnt__', data.length);
            }
            result = stat.result(ctx);
        }
        return result;
    }

    public refresh() {
        const divData = this.divData;
        if (!divData) {
            return;
        }
        divData.clear();
        const conf = this.conf;
        const columns = conf.columns ? conf.columns : 1;
        const dataSet = this.dataSet;
        const ci = dataSet.columns;
        const cols = conf.cols ? conf.cols : Object.keys(dataSet.columns).map(
            c => ({ th: c, td: c } as ColumnDefinition)
        );
        const labels = dataSet.labels;
        const colgroup = Tag.of('colgroup');
        const headers = Tag.of('tr').cls('header');
        for (let i = 0; i < columns; ++i) {
            for (const col of cols) {
                const c = col as ColumnDefinition;
                const colTag = Tag.of('col').putInto(colgroup);
                const th = Tag.of('th');
                DbTable.addContent(th, labels, c.th, ci);
                colTag.style({ width: c.width });
                th.putInto(headers);
            }
            if (this.formUpdate) {
                Tag.of('col').style({ width: '2ex' }).putInto(colgroup);
                Tag.of('th').putInto(headers);
            }
            if (_TABLE_CAN_DELETE) {
                Tag.of('col').style({ width: '2ex' }).putInto(colgroup);
                Tag.of('th').putInto(headers);
            }
        }
        const table = Tag.of<HTMLTableElement>('table').cls('stylized').addAll(colgroup, headers);
        const totalColumns = colgroup.get().childNodes.length;
        let data = dataSet.data;
        if (conf.filters) {
            for (const filter of conf.filters) {
                data = filter.filter(data);
            }
        }
        const group = conf.group;
        if (group) {
            const keyCol = group.key;
            const grouped: { [index: string]: any[][] } = {};
            for (const d of data) {
                const key = d[ci[keyCol]];
                if (!grouped[key]) {
                    grouped[key] = [];
                }
                grouped[key].push(d);
            }
            const keys = Object.keys(grouped);
            if (group.sort) {
                keys.sort(group.sort);
            }
            if (group.nav) {
                Tag.of('form').add(Tag.fieldset(group.nav).cls('links').add(
                    keys.map((k) => Tag.span(Tag.a(k).attr({ href: '#' + k })))
                )).putInto(divData);
            }
            for (const key of keys) {
                const result = this.getStat(grouped[key], ci);
                Tag.of('tr').cls('top').add(
                    Tag.of('td').cls('group').attr({ colspan: totalColumns })
                        .add(Tag.of('span').add(Tag.a(key).name(key)))
                        .add(Tag.of('span').cls('stat').add(result))
                ).putInto(table);
                this.addToTable(table, grouped[key], columns);
            }
        } else {
            this.addToTable(table, data, columns);
        }
        const result = this.getStat(data, ci);
        Tag.div(Tag.of('span').cls('stat').add(result)).putInto(divData);
        table.putInto(divData);
    }

    private addToTable(table: Tag<HTMLTableElement>, data: any[][], columns: number) {
        const conf = this.conf;
        const dataSet = this.dataSet;
        const ci = dataSet.columns;
        if (conf.sort) {
            const sort = conf.sort;
            data.sort((a, b) => sort((col) => a[ci[col]], (col) => b[ci[col]]));
        }
        const cols = this.conf.cols;
        if (cols) {
            let colCount = 0;
            let alt = false;
            let tr: Tag<HTMLElement> | null = null;
            for (const dt of data) {
                if (!tr) {
                    tr = Tag.of('tr').cls(alt ? 'alt' : 'def');
                    alt = !alt;
                }
                for (const col of cols) {
                    const c = col as ColumnDefinition;
                    const td = Tag.of('td');
                    td.style({ 'text-align': c.align })
                    DbTable.addContent(td, dt, c.td, ci);
                    tr.add(td);
                }
                const formUpdate = this.formUpdate;
                if (formUpdate) {
                    Tag.of('td').add(Tag.a(Tag.icon('pencil-square')).toolTip(() => {
                        const d = dt;
                        DbTable.setFormData(formUpdate.get(), (k) => d[ci[k]]);
                        return {
                            body: formUpdate,
                            width: '70%',
                        }
                    })).putInto(tr);
                }
                if (_TABLE_CAN_DELETE) {
                    Tag.of('td').add(Tag.a(Tag.icon('x-square')).event('click', () => {
                        const r = confirm('Are you sure to delete item [' + dt + ']?');
                        if (r) {
                            const data: { [index: string]: any } = {};
                            for (const f in _TABLE_FIELDS) {
                                if (_TABLE_FIELDS[f].primary) {
                                    data[f] = dt[ci[f]];
                                }
                            }
                            Ajax.delete(
                                (r) => {
                                    this.showMsg(r);
                                    this.loadData();
                                },
                                JSON.stringify(data),
                                '',
                                Mime.JSON,
                                Mime.HTML
                            );
                        }
                    })).putInto(tr);
                }
                colCount++;
                if (colCount == columns) {
                    tr.putInto(table);
                    tr = null;
                    colCount = 0;
                }
            }
            if (tr) {
                tr.putInto(table);
            }
        }
    }

    loadData() {
        Ajax.get((r, t) => {
            if (t === Mime.JSON) {
                const dataSet = JSON.parse(r);
                this.conf.pre?.(dataSet);
                this.data(dataSet).refresh();
            } else {
                this.divData.clear();
                this.popupMsg?.html(r).show();
            }
        });
    }
}

declare const dbTableConfig: () => DbTableConfig;

onLoad(() => {
    new DbTable((typeof dbTableConfig === 'function') ? dbTableConfig() : {})
        .render('main')
        .loadData();
});
