<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

function xmldb_datafield_datestamp_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2019102705) {
        $fields = $DB->get_records('data_fields', ['type' => 'datestamp']);
        foreach ($fields as $field) {
            $data = $DB->get_record('data', ['id' => $field->dataid]);
            $coursecontext = context_course::instance($data->course);
            $fielduserids = [];
            $roleids = datafield_datestamp_getusers($field);
            foreach ($roleids as $roleid) {
                $users = get_role_users($roleid, $coursecontext);
                foreach ($users as $user) {
                    $fielduserids[] = $user->id;
                }
            }
            $field->{DATAFIELD_DATESTAMP_COLUMN_FIELD_SUBMITABLEUSERS} = implode(',', $fielduserids);
            $DB->update_record('data_fields', $field);
        }
        upgrade_plugin_savepoint(true, 2019102705, 'datafield', 'datestamp');
    }

    return true;
}
