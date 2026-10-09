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

use mod_eurooffice\api\filesystem;

/**
 * Define all the restore steps that will be used by the restore_eurooffice_activity_task.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_eurooffice_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths    = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('eurooffice', '/activity/eurooffice');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a eurooffice restore.
     *
     * @param  object $data The data in object form
     * @return void
     */
    protected function process_eurooffice($data) {
        global $DB;

        $data         = (object) $data;
        $data->course = $this->get_courseid();

        $data->timecreated  = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('eurooffice', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore the related files after the database structure is ready.
     * @return void
     */
    protected function after_execute() {
        // Add eurooffice related files.
        $this->add_related_files('mod_eurooffice', 'intro', null);
        $this->add_related_files('mod_eurooffice', filesystem::FILEAREA_INITIAL, null);
    }
}
