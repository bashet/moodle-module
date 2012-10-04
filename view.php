<?php

// This file is part of the EQUELLA Moodle Integration - https://github.com/equella/moodle-module
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/resourcelib.php');

global $DB, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // newmodule ID

if( $id ) {  // Two ways to specify the module
    $cm = get_coursemodule_from_id('equella', $id, 0, false, MUST_EXIST);
    $equella = $DB->get_record('equella', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $equella = $DB->get_record('url', array('id' => $a), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('url', $equella->id, $equella->course, false, MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/equella:view', $context);

if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
	notice(get_string("activityiscurrentlyhidden"));
}

add_to_log($course->id, "equella", "view", "view.php?id=$cm->id", $equella->id, $cm->id);

$PAGE->set_url('/mod/equella/view.php', array('id' => $cm->id));

$url = equella_appendtoken($equella->url);
if (optional_param('inpopup', 0, PARAM_BOOL))
{
	redirect($url);
}

$PAGE->set_title($course->shortname . ': ' . $equella->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($equella);
echo $OUTPUT->header();

if( trim(strip_tags($equella->intro)) ) {
	echo $OUTPUT->box_start('mod_introbox', 'equellaintro');
	echo format_module_intro('equella', $equella, $cm->id);
	echo $OUTPUT->box_end();
}

$mimetype = resourcelib_guess_url_mimetype($equella->url);
$link = html_writer::tag('a', $equella->name, array('href'=>str_replace('&amp;', '&', $url)));
$clicktoopen = get_string('clicktoopen', 'equella', $link);

/////////////////////////////////////////////////////////////
//
// The following is copied from /mod/resource/locallib.php
//
$mediarenderer = $PAGE->get_renderer('core', 'media');
$embedoptions = array(
    core_media::OPTION_TRUSTED => true,
    core_media::OPTION_BLOCK => true,
);
$moourl = new moodle_url($url);

if (file_mimetype_in_typegroup($mimetype, 'web_image')) {
	// It's an image
    echo resourcelib_embed_image($url, null);

} else if ($mimetype === 'application/pdf') {
    // PDF document
    echo resourcelib_embed_pdf($url, null, $clicktoopen);

} else if ($mediarenderer->can_embed_url($moourl, $embedoptions)) {
    // Media (audio/video) file.
    echo $mediarenderer->embed_url($moourl, null, 0, 0, $embedoptions);

} else {
    // anything else - just try object tag enlarged as much as possible
    echo resourcelib_embed_general($url, null, $clicktoopen, $mimetype);
}

echo $OUTPUT->footer($course);
?>

