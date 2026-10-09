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
 * Define all the backup steps that will be used by the backup_eurooffice_activity_task.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_eurooffice_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure for the eurooffice activity.
     * @return backup_nested_element
     */
    protected function define_structure() {
        // Define each element separated.
        $eurooffice = new backup_nested_element(
            'eurooffice',
            ['id'],
            [
                'name',
                'intro',
                'introformat',
                'initialfiletype',
                'timecreated',
                'timemodified',
            ]
        );

        // Define sources.
        $eurooffice->set_source_table('eurooffice', ['id' => backup::VAR_ACTIVITYID]);

        // Define file annotations.
        $eurooffice->annotate_files('mod_eurooffice', 'intro', null); // This file area hasn't itemid.
        $eurooffice->annotate_files('mod_eurooffice', filesystem::FILEAREA_INITIAL, null);

        // Return the root element (eurooffice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($eurooffice);
    }
}
