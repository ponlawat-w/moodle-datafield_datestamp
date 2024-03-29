<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('c', PARAM_INT);

require_course_login($courseid);

$recordid = required_param('r', PARAM_INT);
$fieldid = required_param('f', PARAM_INT);

$field = $DB->get_record('data_fields', ['id' => $fieldid]);
if (!$field) {
    throw new moodle_exception('Field not found');
}

if (!datafield_datestamp_submitable($field)) {
    throw new moodle_exception('No permission');
}

$record = $DB->get_record('data_records', ['id' => $recordid]);
if (!$record) {
    throw new moodle_exception('Record not found');
}

$comment = optional_param('comment', '', PARAM_TEXT);

$content = $DB->get_record('data_content', ['fieldid' => $fieldid, 'recordid' => $recordid]);
if ($content && $content->content) {
    $content->content = time();
    $content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_COMMENT} = $comment;
    $content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_MODIFIED} = 'true';
    $DB->update_record('data_content', $content);
    redirect(new moodle_url('/mod/data/view.php', ['rid' => $recordid]));
    exit;
}
if ($content) {
    $DB->delete_records('data_content', ['id' => $content->id]);
}

$content = new stdClass();
$content->fieldid = $fieldid;
$content->recordid = $recordid;
$content->content = time();
$content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_USERID} = $USER->id;
$content->{DATAFIELD_DATESTAMP_COLUMN_CONTENT_COMMENT} = $comment;
if (!$DB->insert_record('data_content', $content)) {
    throw new moodle_exception('Cannot insert record');
}

redirect(new moodle_url('/mod/data/view.php', ['rid' => $recordid]));
