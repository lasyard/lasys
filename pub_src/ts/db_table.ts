import { SortFun } from './common';
import { Tag, TagContent } from './tag';
import { ToolTip } from './tool_tip';
import { onLoad } from './html';
import { MimeType, Ajax, HttpMethod } from './ajax';
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
    cols?: ColumnDefinition[];
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
    private panel: Tag<HTMLElement> = null;
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
        return this;
    }

    private setFormData(data: any[]) {
        const ci = this.dataSet.columns;
        const form = this.updateForm;
        for (const col in ci) {
            const field = form.get().elements.namedItem(col);
            if (field instanceof HTMLInputElement) {
                (field as HTMLInputElement).value = data[ci[col]];
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
                data[f.name] = f.value;
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
        this.panel = panel;
        const insertForm = Tag.form('-form-db-insert');
        if (insertForm) {
            insertForm.ajaxfy(
                function (res) {
                    Tag.byId('-meta-div-edit-form').hide();
                    DbTable.showMsg(res);
                    self.loadData();
                },
                function (form) {
                    return DbTable.retrieveFormData(form);
                },
                HttpMethod.POST,
                MimeType.HTML,
            );
        }
        const updateForm = Tag.form('-form-db-update');
        const self = this;
        if (updateForm) {
            const callback = function (res: string) {
                ToolTip.get().hide();
                DbTable.showMsg(res);
                self.loadData();
            };
            // Do this before updateForm vanished.
            const btn = Tag.byId('-span-insert-new').find('a');
            if (btn) {
                btn.event('click', function (e) {
                    const form = updateForm.get();
                    const data = DbTable.retrieveFormData(form);
                    for (const f in _TABLE_FIELDS) {
                        if (_TABLE_FIELDS[f].auto) {
                            delete data[f];
                        }
                    }
                    Ajax.call(callback, HttpMethod.POST, JSON.stringify(data), form.action, MimeType.HTML);
                    e.preventDefault();
                });
            }
            updateForm.vanish().show().ajaxfy(
                callback,
                function (form) {
                    const data = DbTable.retrieveFormData(form);
                    const keys: { [index: string]: any } = {};
                    for (const f in data) {
                        if (_TABLE_FIELDS[f].primary) {
                            keys[f] = data[f];
                            delete data[f];
                        }
                    }
                    return { keys: keys, data: data };
                },
                HttpMethod.PUT,
                MimeType.HTML,
            );
            this.updateForm = updateForm;
        }
        return this;
    }

    private static addContent(t: Tag<HTMLElement>, data: any[], fun: string | ContentFun, ci: ColumnIndices) {
        if (typeof fun === 'string') {
            t.add(data[ci[fun]]);
        } else if (typeof fun === 'function') {
            t.add(fun((col) => data[ci[col]]));
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
                Tag.of('col').style({ width: col.width }).putInto(colgroup);
                const th = Tag.of('th');
                DbTable.addContent(th, labels, col.th, ci);
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
                DbTable.addContent(td, dt, col.td, ci);
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
                        const url = new URL(window.location.href);
                        for (const f in _TABLE_FIELDS) {
                            if (_TABLE_FIELDS[f].primary) {
                                url.searchParams.append(f, dt[ci[f]]);
                            }
                        }
                        Ajax.delete(function (res) {
                            DbTable.showMsg(res);
                            self.loadData();
                        }, url.href, MimeType.HTML);
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
