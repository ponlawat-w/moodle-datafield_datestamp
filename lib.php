<?php

defined('MOODLE_INTERNAL') || die();

const DATAFIELD_DATESTAMP_COLUMN_FIELD_SUBMITABLEROLES = 'param1';

function datafield_datestamp_getrolecheckboxes($roles, $selectedroles = []) {
    $str = '';
    foreach ($roles as $id => $role) {
        $attr = [
            'type' => 'checkbox',
            'value' => $id,
            'name' => DATAFIELD_DATESTAMP_COLUMN_FIELD_SUBMITABLEROLES . '[]'
        ];

        if (in_array($id, $selectedroles)) {
            $attr['checked'] = 'checked';
        }

        $str .= html_writer::div(
            html_writer::tag('label',
                html_writer::start_tag('input', $attr) . $role
            )
        );
    }

    return $str;
}

function datafield_datestamp_getroles($field) {
    return explode(',', $field->{DATAFIELD_DATESTAMP_COLUMN_FIELD_SUBMITABLEROLES});
}

function datafield_datestamp_submitable($field, $coursecontext, $userid = 0) {
    global $USER;
    $submittableroles = datafield_datestamp_getroles($field);
    $userid = $userid ? $userid : $USER->id;
    $myroles = get_user_roles($coursecontext, $userid);
    foreach ($myroles as $myrole) {
        if ($myrole->userid == $userid && in_array($myrole->roleid, $submittableroles)) {
            return true;
        }
    }

    return false;
}

function datafield_datestamp_getcontent($recordid, $fieldid) {
    global $DB;
    $content = $DB->get_record('data_content', [
        'recordid' => $recordid,
        'fieldid' => $fieldid
    ]);

    return $content ? $content->content : null;
}

function datafield_datestamp_getbadge($content) {
    $str = $content ?
        userdate($content, get_string('strftimedate', 'langconfig'))
        : get_string('nostamp', 'datafield_datestamp');
    $class = $content ? 'badge badge-success' : 'badge badge-secondary';
    return html_writer::span($str, $class);
}

function datafield_datestamp_getsingletemplate($recordid, $field) {
    global $COURSE, $OUTPUT;
    $content = datafield_datestamp_getcontent($recordid, $field->id);
    $badge = datafield_datestamp_getbadge($content);

    $stampaction = '';
    if (datafield_datestamp_submitable($field, context_course::instance($COURSE->id)) && !$content) {
        $stampaction = ' ' .
            html_writer::link(new moodle_url('/mod/data/field/datestamp/stamp.php', [
                'c' => $COURSE->id,
                'r' => $recordid,
                'f' => $field->id
            ]),
                $OUTPUT->pix_icon('stamp', '', 'datafield_datestamp') . ' ' .
                get_string('stamptoday', 'datafield_datestamp'), [
                    'class' => 'btn btn-primary'
                ]);
    }

    return $badge . $stampaction;
}
