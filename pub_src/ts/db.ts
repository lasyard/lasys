import { SortFun } from './common';
import { Tag, TagContent } from './tag';
import { ToolTip } from './tool_tip';
import { onLoad } from './html';
import { MimeType, Ajax } from './ajax';
import { Filter } from './filter';

export type ColumnIndices = { [index: string]: number };

interface DataSet {
    columns: ColumnIndices;
    labels: string[];
    data: any[][];
}

type ValueCallback = (col: string) => any;
type ContentFun = (d: ValueCallback) => TagContent;
type StatFun = (data: ValueCallback, context: any) => void;
type ResultFun = (context: any) => TagContent;

interface ColumnDefinition {
    th?: string | ContentFun;
    td: string | ContentFun;
    width?: string;
}

interface StatObject {
    init: () => any;
    fun: StatFun,
    result: ResultFun,
}

interface DbTableConfig {
    cols?: (ColumnDefinition | string)[];
    columns?: number;
    filters?: Filter[];
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
    private dataSet: DataSet;
    private conf: DbTableConfig;
    private divFilters: Tag<HTMLDivElement> = null;
    private divData: Tag<HTMLDivElement> = null;
    private updateForm: Tag<HTMLFormElement> = null;

    constructor(conf: DbTableConfig) {
        this.conf = conf;
    }

    private data(dataSet: DataSet) {
        this.dataSet = dataSet;
        if (this.divFilters) {
            const div = this.divFilters;
            div.clear();
            for (const filter of this.conf.filters) {
                div.add(filter.render(dataSet.data, dataSet.columns, this.refresh.bind(this)));
            }
        }
        if (!this.conf.cols) {
            this.conf.cols = Object.keys(dataSet.columns).map(
                c => ({ th: c, td: c } as ColumnDefinition)
            );
        }
        return this;
    }

    private setFormData(data: any[]) {
        const ci = this.dataSet.columns;
        const form = this.updateForm;
        for (const col in ci) {
            const field = form.get().elements.namedItem(col);
            if (field instanceof HTMLInputElement) {
                const f = field as HTMLInputElement;
                const v = data[ci[col]];
                if (f.type === 'checkbox') {
                    f.checked = (v == '1' ? true : false);
                } else {
                    f.value = v;
                }
            } else if (field instanceof HTMLTextAreaElement) {
                (field as HTMLTextAreaElement).value = data[ci[col]];
            } else if (field instanceof HTMLSelectElement) {
                (field as HTMLSelectElement).value = data[ci[col]];
            }
        }
    }

    private static retrieveFormData(form: HTMLFormElement) {
        const data: { [index: string]: any } = {};
        for (let i = 0; i < form.elements.length; ++i) {
            const f = form.elements[i];
            if (
                f instanceof HTMLInputElement
                || f instanceof HTMLTextAreaElement
                || f instanceof HTMLSelectElement
            ) {
                if (f.type === 'submit') {
                    continue;
                }
                if (f.type === 'checkbox') {
                    data[f.name] = (f as HTMLInputElement).checked ? 1 : 0;
                } else {
                    data[f.name] = f.value;
                }
            }
        }
        return data;
    }

    private static showMsg(msg: string) {
        Tag.byId('-ajax-msg').html(msg).show();
    }

    render(panelId: string) {
        const panel = Tag.byId(panelId);
        if (!panel) {
            return;
        }
        if (this.conf.filters) {
            this.divFilters = Tag.div().putInto(panel);
        }
        this.divData = Tag.div().putInto(panel);
        const btnInsert = Tag.byId('-btn-insert');
        const divForm = Tag.byId('-div-form-insert');
        if (btnInsert && divForm) {
            btnInsert.event('click', (e) => {
                divForm.show();
                e.stopPropagation();
            });
            divForm.outClickHide();
        }
        const self = this;
        const formInsert = Tag.form('-form-insert');
        if (formInsert) {
            const form = formInsert.get();
            const action = form.getAttribute('action');
            formInsert.event('submit', function (e) {
                const data = DbTable.retrieveFormData(form);
                Ajax.post(
                    function (res) {
                        divForm.hide();
                        DbTable.showMsg(res);
                        self.loadData();
                    },
                    JSON.stringify(data),
                    action,
                    MimeType.JSON,
                    MimeType.HTML
                );
                e.preventDefault();
            });
        }
        const formUpdate = Tag.form('-form-update');
        if (formUpdate) {
            const callback = function (res: string) {
                ToolTip.get().hide();
                DbTable.showMsg(res);
                self.loadData();
            };
            const form = formUpdate.get();
            // Do not use `form.action`, which may overrided by an input named `action`.
            const action = form.getAttribute('action');
            // Do this before updateForm vanished.
            const btn = Tag.byId('-span-insert-new').find('a');
            if (btn) {
                btn.event('click', function (e) {
                    const data = DbTable.retrieveFormData(form);
                    for (const f in _TABLE_FIELDS) {
                        if (_TABLE_FIELDS[f].auto) {
                            delete data[f];
                        }
                    }
                    Ajax.post(
                        callback,
                        JSON.stringify(data),
                        action,
                        MimeType.JSON,
                        MimeType.HTML
                    );
                    e.preventDefault();
                });
            }
            formUpdate.vanish().show().event('submit', function (e) {
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
                    action,
                    MimeType.JSON,
                    MimeType.HTML
                );
                e.preventDefault();
            });
            this.updateForm = formUpdate;
        }
        return this;
    }

    private static addContent(t: Tag<HTMLElement>, data: any[], fun: string | ContentFun, ci: ColumnIndices) {
        let c: any;
        if (typeof fun === 'string') {
            c = data[ci[fun]];
        } else if (typeof fun === 'function') {
            c = fun((col) => data[ci[col]])
        }
        if (c) {
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
            result = stat.result(ctx);
        }
        return result;
    }

    private refresh() {
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
                const colTag = Tag.of('col').putInto(colgroup);
                const th = Tag.of('th');
                if (typeof col === 'string') {
                    DbTable.addContent(th, labels, col, ci);
                } else {
                    DbTable.addContent(th, labels, col.th, ci);
                    colTag.style({ width: col.width });
                }
                th.putInto(headers);
            }
            if (this.updateForm) {
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
                keys.sort(conf.group.sort);
            }
            if (group.nav) {
                Tag.of('form').add(Tag.fieldset(group.nav).cls('links').add(
                    keys.map((k) => Tag.span(Tag.of('a').attr({ href: '#' + k }).add(k)))
                )).putInto(divData);
            }
            for (const key of keys) {
                const result = this.getStat(grouped[key], ci);
                Tag.of('tr').cls('top').add(
                    Tag.of('td').cls('group').attr({ colspan: totalColumns })
                        .add(Tag.of('span').add(Tag.of('a').name(key).add(key)))
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
            data.sort((a, b) => conf.sort((col) => a[ci[col]], (col) => b[ci[col]]));
        }
        let colCount = 0;
        let alt = false;
        let tr = null;
        const cols = this.conf.cols;
        const self = this;
        for (const dt of data) {
            if (colCount == 0) {
                tr = Tag.of('tr').cls(alt ? 'alt' : 'def');
                alt = !alt;
            }
            for (const col of cols) {
                const td = Tag.of('td');
                DbTable.addContent(td, dt, typeof col === 'string' ? col : col.td, ci);
                tr.add(td);
            }
            if (this.updateForm) {
                Tag.of('td').add(Tag.icon('pencil-square')).putInto(tr).toolTip(function () {
                    const data = dt;
                    self.setFormData(data);
                    return {
                        body: self.updateForm,
                        width: '70%',
                    }
                });
            }
            if (_TABLE_CAN_DELETE) {
                Tag.of('td').add(Tag.icon('x-square')).putInto(tr).event('click', () => {
                    const r = confirm('Are you sure to delete item [' + dt + ']?');
                    if (r) {
                        const data: { [index: string]: any } = {};
                        for (const f in _TABLE_FIELDS) {
                            if (_TABLE_FIELDS[f].primary) {
                                data[f] = dt[ci[f]];
                            }
                        }
                        Ajax.delete(
                            function (res) {
                                DbTable.showMsg(res);
                                self.loadData();
                            },
                            JSON.stringify(data),
                            '',
                            MimeType.HTML
                        );
                    }
                });
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

    loadData() {
        Ajax.get((r) => {
            this.data(JSON.parse(r)).refresh();
        });
    }
}

declare const dbTableConfig: () => DbTableConfig;

onLoad(function () {
    new DbTable((typeof dbTableConfig === 'function') ? dbTableConfig() : {}).render('main').loadData();
});
