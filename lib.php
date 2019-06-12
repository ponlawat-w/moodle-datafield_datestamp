<?php

defined('MOODLE_INTERNAL') || die();

const DATAFIELD_DATESTAMP_COLUMN_FIELD_SUBMITABLEROLES = 'param1';
const DATAFIELD_DATESTAMP_COLUMN_FIELD_STAMPTODAYTEXT = 'param2';
const DATAFIELD_DATESTAMP_COLUMN_CONTENT_USERID = 'content1';
const DATAFIELD_DATESTAMP_COLUMN_CONTENT_COMMENT = 'content2';

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
    if (isset($USER->access['rsw'])
        && isset($USER->access['rsw'][$coursecontext->path])
        && in_array($USER->access['rsw'][$coursecontext->path], $submittableroles)) {
        return true;
    }

    $myroles = get_user_roles_with_special($coursecontext, $userid);
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

    return $content ? $content : null;
}

function datafield_datestamp_getbadge($content, $withuser = true) {
    global $DB;
    $submitted = $content && $content->content ? true : false;
    $datestr = $submitted ?
        userdate($content->content, get_string('strftimedate', 'langconfig'))
        : get_string('nostamp', 'datafield_datestamp');
    $class = $submitted ? 'badge badge-danger' : 'badge badge-secondary';
    $attr = $submitted ? ['style' => 'background-color: #da4f49;'] : [];
    if ($submitted && $content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_USERID}) {
        $user = $DB->get_record('user', ['id' => $content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_USERID}]);
        if ($user) {
            $badgestr = $withuser ? get_string('stampedwithuser', 'datafield_datestamp', [
                'name' => fullname($user),
                'date' => $datestr
            ]) : get_string('stampedwithoutuser', 'datafield_datestamp', [
                'date' => $datestr
            ]);
            return html_writer::span(
                $badgestr,
                $class,
                $attr
            );
        }
    }
    return html_writer::span($datestr, $class, $attr);
}

function datafield_datestamp_getstamptext($field) {
    return $field->{DATAFIELD_DATESTAMP_COLUMN_FIELD_STAMPTODAYTEXT} ?
        $field->{DATAFIELD_DATESTAMP_COLUMN_FIELD_STAMPTODAYTEXT} :
        get_string('stamptoday', 'datafield_datestamp');
}

function datafield_datestamp_contentsubmitted($contentrecord) {
    if ($contentrecord) {
        return $contentrecord->content ? true : false;
    }

    return false;
}

function datafield_datestamp_getsingletemplate($recordid, $field) {
    global $COURSE, $OUTPUT;
    $content = datafield_datestamp_getcontent($recordid, $field->id);
    $badge = datafield_datestamp_getbadge($content);

    $afterbadgehtml = '';
    if (datafield_datestamp_submitable($field, context_course::instance($COURSE->id)) && !datafield_datestamp_contentsubmitted($content)) {
        $stampactionform = html_writer::start_tag('form', [
            'action' => new moodle_url('/mod/data/field/datestamp/stamp.php'),
            'method' => 'post',
            'enctype' => 'application/x-www-form-urlencoded'
        ]);

        $stampactionform .= html_writer::start_tag('input', ['type' => 'hidden', 'name' => 'c', 'value' => $COURSE->id]);
        $stampactionform .= html_writer::start_tag('input', ['type' => 'hidden', 'name' => 'r', 'value' => $recordid]);
        $stampactionform .= html_writer::start_tag('input', ['type' => 'hidden', 'name' => 'f', 'value' => $field->id]);
        $stampactionform .= html_writer::start_tag('input', [
            'type' => 'text',
            'name' => 'comment',
            'placeholder' => get_string('commentplaceholder', 'datafield_datestamp')
        ]);
        $stampactionform .= html_writer::tag('button',
            $OUTPUT->pix_icon('stamp', '', 'datafield_datestamp') . ' ' .
            datafield_datestamp_getstamptext($field), [
                'type' => 'submit',
                'class' => 'btn btn-warning'
            ]);

        $stampactionform .= html_writer::end_tag('form');

        $afterbadgehtml = html_writer::div($stampactionform, '', [
            'style' => 'margin: 8px 0;',
            'class' => 'datafield_datestamp-stampaction',
            'data-action' => new moodle_url('/mod/data/field/datestamp/stamp.php'),
            'data-method' => 'post',
            'data-enctype' => 'application/x-www-form-urlencoded'
        ]);
    } else if ($content && $content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_COMMENT}) {
        $afterbadgehtml = html_writer::div(
            html_writer::span(get_string('comment', 'datafield_datestamp'), '' , [
                'style' => 'font-weight: bold;'
            ]) . ': ' .
            $content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_COMMENT}
        , '', ['style' => 'font-style: italic;']);
    }

    return $badge . $afterbadgehtml;
}
