/**
 * Turns an importer's error lines into a downloadable CSV a person can work through while fixing the file.
 * The importers report one line per problem, e.g. "Baris 14 (Kickoff): End Date lebih awal dari Start Date — baris dilewati";
 * a warning is prefixed "[Peringatan]". Lines that do not follow that shape still go in the Problem column.
 *
 *   downloadImportErrors(errors, 'planning-import-errors')
 */
(function () {
    var LINE = /^(\[Peringatan\]\s*)?(?:Baris|Row)\s+(\d+)\s*(?:\(([^)]*)\))?\s*:\s*([\s\S]*)$/i;

    function cell(v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; }

    window.downloadImportErrors = function (errors, name) {
        var rows = [['Row', 'Item', 'Type', 'Problem']];
        (errors || []).forEach(function (line) {
            var m = LINE.exec(String(line));
            rows.push(m ? [m[2], m[3] || '', m[1] ? 'Warning' : 'Error', m[4]] : ['', '', 'Error', String(line)]);
        });
        // BOM so Excel reads the UTF-8 text correctly.
        var blob = new Blob(['﻿' + rows.map(function (r) { return r.map(cell).join(','); }).join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = (name || 'import-errors') + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
    };
})();
