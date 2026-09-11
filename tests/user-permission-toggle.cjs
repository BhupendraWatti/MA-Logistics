// Run: node tests/user-permission-toggle.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../app/Views/admin/users.php'), 'utf8');
const start = source.indexOf("$('#usersTable').on('change', '.toggle-permission', function() {");
const end = source.indexOf("$('#assignCompanyId').on('change'", start);
assert.ok(start >= 0 && end > start, 'Permission handler must exist');
let handler;
let posted;
const $ = element => element === '#usersTable'
    ? { on: (event, selector, callback) => { handler = callback; } }
    : { attr: name => element[name], is: () => element.checked };
$.post = (url, payload) => { posted = { url, payload }; return { fail() {} }; };
vm.runInNewContext(source.slice(start, end).replace(/<\?=[\s\S]*?\?>/g, '99'), { $, BASE_URL: '/' });

for (const permission of ['can_create', 'can_edit', 'can_delete']) {
    for (const checked of [true, false]) {
        handler.call({ 'data-user-id': '42', 'data-permission': permission, checked });
        assert.equal(posted.url, '/admin/togglePermission');
        assert.equal(posted.payload.user_id, '42');
        assert.equal(posted.payload.permission, permission);
        assert.equal(posted.payload.value, checked ? 1 : 0);
    }
}
console.log('PASS: all three permission toggles post the correct user, permission and on/off value.');

const editorStart = source.indexOf('function loadSelectedCompanyAssignment()');
const editorEnd = source.indexOf('function renderAssignmentsList(', editorStart);
assert.ok(editorStart >= 0 && editorEnd > editorStart, 'Company assignment editor must exist');
const fields = {};
const editor$ = selector => ({
    val(value) {
        if (arguments.length) fields[selector] = value;
        return fields[selector];
    },
    prop(name, value) {
        fields[`${selector}:${name}`] = value;
    },
});
const editorContext = {
    $: editor$,
    activeCompanyAssignments: [
        { company_id: '1', role: 'user', can_create: '1', can_edit: '0', can_delete: '0' },
        { company_id: '2', role: 'tracking', can_create: '0', can_edit: '1', can_delete: '0' },
    ],
};
vm.runInNewContext(source.slice(editorStart, editorEnd), editorContext);

fields['#assignCompanyId'] = '2';
editorContext.loadSelectedCompanyAssignment();
assert.equal(fields['#assignRole'], 'tracking');
assert.equal(fields['#assignCanCreate:checked'], false);
assert.equal(fields['#assignCanEdit:checked'], true);
assert.equal(fields['#assignCanDelete:checked'], false);

fields['#assignCompanyId'] = '1';
editorContext.loadSelectedCompanyAssignment();
assert.equal(fields['#assignRole'], 'user');
assert.equal(fields['#assignCanCreate:checked'], true);
assert.equal(fields['#assignCanEdit:checked'], false);

assert.match(source, /<input id="assignRole"[^>]*type="text"/);
assert.doesNotMatch(source, /<select id="assignRole"/);
console.log('PASS: selecting a company loads its own role and operation permissions into the editor.');

const companySelect = source.match(/<select[^>]*id="assignCompanyId"[^>]*>/);
assert.ok(companySelect, 'assignCompanyId must exist');
assert.match(companySelect[0], /data-no-track="true"/, 'Switching the assignment being edited must not mark the page dirty');
for (const id of ['assignRole', 'assignCanCreate', 'assignCanEdit', 'assignCanDelete']) {
    const tag = source.match(new RegExp(`<input[^>]*id="${id}"[^>]*>`));
    assert.ok(tag, `${id} must exist`);
    assert.doesNotMatch(tag[0], /data-no-track="true"/, `${id} must warn when changed without saving`);
}
assert.match(source, /window\.resetDirty\?\.\(\);/);
const utilities = fs.readFileSync(path.join(__dirname, '../public/js/erp-utils.js'), 'utf8');
assert.match(utilities, /window\.resetDirty = resetDirty;/);
console.log('PASS: unsaved assignment edits are tracked and a successful AJAX save clears the dirty flag.');

assert.match(source, /openCompanyAccessDrawer\(\$\{parseInt\(row\.id, 10\)\}, decodeURIComponent\('\$\{encodeURIComponent\(String\(row\.username \|\| ''\)\)\}'\)\)/);
assert.match(source, /revokeCompanyAccess\(\$\{parseInt\(item\.company_id, 10\)\}, decodeURIComponent\('\$\{encodeURIComponent\(String\(item\.company_name \|\| ''\)\)\}'\)\)/);
console.log('PASS: names in inline assignment actions are URI-encoded before entering JavaScript string literals.');
