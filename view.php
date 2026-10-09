<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * View page
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'eurooffice');

$eurooffice = $DB->get_record('eurooffice', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = \CONTEXT_MODULE::instance($cm->id);
require_capability('mod/eurooffice:view', $context);

$mycfg = get_config('mod_eurooffice');

// Handle completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/eurooffice/view.php', ['id' => $cm->id]);
$PAGE->set_title($eurooffice->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// We need the record here, because the editor can not use a cm_info object!
if ($cm instanceof cm_info) {
    $cmrecord = $cm->get_course_module_record();
}

if (!\mod_eurooffice\api\api::test_connection($mycfg->url)) {
    \core\notification::error(get_string('error_connecting', 'mod_eurooffice'));
} else {
    $templatecontext = [
        'cmid' => $cmid,
    ];
    echo $OUTPUT->render_from_template('mod_eurooffice/content', $templatecontext);
    $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
    $PAGE->requires->js_call_amd(
        'mod_eurooffice/eurooffice_editor',
        'init',
        [
            $course->id,
            $context->id,
            $cmid,
            $courseurl->out(),
        ]
    );
}

echo $OUTPUT->footer();
