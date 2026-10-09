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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/eurooffice/backup/moodle2/restore_eurooffice_stepslib.php'); // Because it exists (must).

/**
 * eurooffice restore task that provides all the settings and steps to perform one complete restore of the activity.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_eurooffice_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new restore_eurooffice_activity_structure_step('eurooffice_structure', 'eurooffice.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder.
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('eurooffice', ['intro'], 'eurooffice');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder.
     */
    public static function define_decode_rules() {
        $rules = [];

        // List of eurooffices in course.
        $rules[] = new restore_decode_rule('EUROOFFICEINDEX', '/mod/eurooffice/index.php?id=$1', 'course');
        // The eurooffice by cm->id and eurooffice->id.
        $rules[] = new restore_decode_rule('EUROOFFICEVIEWBYID', '/mod/eurooffice/view.php?id=$1', 'course_module');

        return $rules;
    }
}
