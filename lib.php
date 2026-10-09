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
 * Mandatory public API of url module
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_eurooffice\api\filesystem;

/**
 * Checks whether or not a feature is supported.
 *
 * @param  string $feature
 * @return mixed  True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function eurooffice_supports($feature) {

    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;

        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;

        default:
            return null;
    }
}

/**
 * Create a new eurooffice instance.
 *
 * @param  \stdClass              $eurooffice
 * @param  ?mod_eurooffice_mod_form $mform
 * @return int
 */
function eurooffice_add_instance(stdClass $eurooffice, $mform) {
    global $DB;

    $eurooffice->timecreated = time();
    $eurooffice->timemodified = time();

    // Save the 'initial file'.
    if (!filesystem::store_initial_file($eurooffice, $eurooffice->coursemodule)) {
        return 0;
    }
    try {
        filesystem::store_file_from_initial($eurooffice->coursemodule);
    } catch (\moodle_exception $e) {
        return 0;
    }

    $eurooffice->id = $DB->insert_record('eurooffice', $eurooffice);

    $completiontimeexpected = !empty($data->completionexpected) ? $eurooffice->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $eurooffice->coursemodule,
        'eurooffice',
        $eurooffice->id,
        $completiontimeexpected
    );

    return $eurooffice->id;
}

/**
 * Update a eurooffice instance.
 *
 * @param  \stdClass $eurooffice
 * @return bool
 */
function eurooffice_update_instance(stdClass $eurooffice) {
    global $DB;

    $eurooffice->id           = $eurooffice->instance;
    $eurooffice->timemodified = time();

    $DB->update_record('eurooffice', $eurooffice);

    $completiontimeexpected = !empty($eurooffice->completionexpected) ? $eurooffice->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $eurooffice->coursemodule,
        'eurooffice',
        $eurooffice->id,
        $completiontimeexpected
    );

    // Do not save the 'initial file' here, as you cannot change this after the activity has been created.
    return true;
}

/**
 * Delete an existing eurooffice instance.
 *
 * @param  int  $id
 * @return bool
 */
function eurooffice_delete_instance($id) {
    global $DB;

    if (!$eurooffice = $DB->get_record('eurooffice', ['id' => $id])) {
        return true; // The instance is deleted already.
    }

    $cm = get_coursemodule_from_instance('eurooffice', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'eurooffice', $id, null);

    $DB->delete_records('eurooffice', ['id' => $eurooffice->id]);

    return true;
}

/**
 * Register the ability to handle drag and drop file uploads.
 *
 * @return array containing details of the files / types the mod can handle
 */
function eurooffice_dndupload_register() {
    global $DB;

    // Prevent using this hook while disabled.
    if (!$DB->get_field('modules', 'visible', ['name' => 'eurooffice'])) {
        return [];
    }

    $extensions = filesystem::get_accepted_types();
    $strdnd     = get_string('dnduploadeurooffice', 'mod_eurooffice');
    $files      = [];
    foreach ($extensions as $extn) {
        $extn    = trim($extn, '.');
        $files[] = ['extension' => $extn, 'message' => $strdnd];
    }

    return ['files' => $files];
}

/**
 * Handle a file that has been uploaded.
 *
 * @param  object $uploadinfo details of the file / content that has been uploaded
 * @return int    instance id of the newly created mod
 */
function eurooffice_dndupload_handle($uploadinfo) {
    global $DB;

    // Prevent using this hook while disabled.
    if (!$DB->get_field('modules', 'visible', ['name' => 'eurooffice'])) {
        return false;
    }

    // Gather the required info.
    $data                          = new stdClass();
    $data->course                  = $uploadinfo->course->id;
    $data->name                    = $uploadinfo->displayname;
    $data->intro                   = '';
    $data->introformat             = FORMAT_HTML;
    $data->coursemodule            = $uploadinfo->coursemodule;
    $data->initialfile_filemanager = $uploadinfo->draftitemid;
    $data->initialfiletype         = filesystem::FILETYPE_UPLOAD;

    // Set the display options to the site defaults.
    return eurooffice_add_instance($data, null);
}

/**
 * Send a stored_file to the browser.
 *
 * @param  \stdClass|int  $course
 * @param  \stdClass|null $cm
 * @param  \context       $context
 * @param  string         $filearea
 * @param  array          $args
 * @param  bool           $forcedownload
 * @param  array          $options
 * @return void
 */
function eurooffice_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return;
    }
    require_login($course, false, $cm);
    // File link only occurs on the edit settings page, so restrict access to teachers.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($filearea !== filesystem::FILEAREA_INITIAL) {
        return;
    }

    $itemid = (int) array_shift($args);
    if ($itemid !== 0) {
        return;
    }

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args);
    if ($filepath !== '/') {
        $filepath .= '/';
    }

    $fs   = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_eurooffice', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php.
 *
 * @param  \stdClass           $coursemodule
 * @return cached_cm_info|null
 */
function eurooffice_get_coursemodule_info($coursemodule) {
    global $DB;

    if (!$eurooffice = $DB->get_record('eurooffice', ['id' => $coursemodule->instance])) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name    = $eurooffice->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('eurooffice', $eurooffice, $coursemodule->id, false);
    }

    // See if there is at least one file.
    $context = \context_module::instance($coursemodule->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files(
        $context->id,
        'mod_eurooffice',
        filesystem::FILEAREA,
        0,
        'id',
        false,
    );
    if ($files) {
        $file = reset($files);
        if ($fileicon = filesystem::get_module_icon($file)) {
            $info->icon = 'mod/eurooffice/' . $fileicon;
            $info->customdata['filtericon'] = 1; // Apply the monologo filter to the icon.
        }
    }
    return $info;
}

/**
 * Get the editor config as json by using an html fragment.
 *
 * @param  mixed  $args an array or object with context and parameters needed to get the data
 * @return string The html fragment we want to use by ajax
 */
function mod_eurooffice_output_fragment_get_editor_config($args) {
    global $DB;

    $contextid = $args['contextid'] ?? 0;
    $courseid = $args['courseid'] ?? 0;
    $cmid = $args['cmid'] ?? 0;

    if (empty($contextid)) {
        throw new \moodle_exception('Missing param "contextid"');
    }
    if (empty($courseid)) {
        throw new \moodle_exception('Missing param "courseid"');
    }
    if (empty($cmid)) {
        throw new \moodle_exception('Missing param "cmid"');
    }

    $context = \context::instance_by_id($contextid);
    $cm = $DB->get_record('course_modules', ['id' => $cmid]);

    $apiconfig = new \mod_eurooffice\api\config($courseid, $context, $cm);

    return json_encode($apiconfig->get_config());
}

/**
 * Get the userinfos json by using an html fragment.
 * These userinfos are used by the editor to show the user avatars in the co-editor list.
 *
 * @param  mixed  $args an array or object with context and parameters needed to get the data
 * @return string The html fragment we want to use by ajax
 */
function mod_eurooffice_output_fragment_get_user_infos($args) {
    global $DB;

    $contextid = $args['contextid'] ?? 0;
    $userids = $args['userids'] ?? 0;
    $cmid = $args['cmid'] ?? 0;

    if (empty($contextid)) {
        throw new \moodle_exception('Missing param "contextid"');
    }
    if (empty($cmid)) {
        throw new \moodle_exception('Missing param "cmid"');
    }

    $context = \context::instance_by_id($contextid);
    $cm = $DB->get_record('course_modules', ['id' => $cmid]);

    $userids = explode(',', $userids);
    if (empty($userids)) {
        return '';
    }
    $userinfo = new \mod_eurooffice\api\userinfo($userids, $context, $cm);

    return json_encode($userinfo->get_infos());
}
