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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Instance form class
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eurooffice_mod_form extends moodleform_mod {
    /**
     * Get the file link to the document as an html fragment.
     *
     * @return string
     */
    private function get_initial_file_link() {
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $this->context->id, // Param contextid.
            'mod_eurooffice', // Param component.
            filesystem::FILEAREA_INITIAL, // Param filearea.
            false, // Param itemid.
            '', // Param sort.
            false // Param includedirs.
        );
        $file = reset($files);
        if (!$file) {
            return get_string('missingfile', 'mod_eurooffice');
        }
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            true
        );

        return html_writer::link($url, $file->get_filename());
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mycfg = get_config('mod_eurooffice');

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // Section for initialfiletype.
        $formatmenu = ['' => get_string('choose')];
        $formatmenu += filesystem::format_menu();

        if (!$this->_instance) {
            $mform->addElement(
                'select',
                'initialfiletype',
                get_string('initialfiletype', 'mod_eurooffice'),
                $formatmenu
            );
            $mform->addRule('initialfiletype', null, 'required', null, 'client');
            $mform->setDefault('initialfiletype', $mycfg->defaultfiletype ?? filesystem::FILETYPE_SPREADSHEET);

            $mform->addElement(
                'filemanager',
                'initialfile_filemanager',
                get_string('initialfile', 'mod_eurooffice'),
                null,
                filesystem::get_filemanager_opts()
            );
            $mform->hideIf('initialfile_filemanager', 'initialfiletype', 'neq', filesystem::FILETYPE_UPLOAD);
        } else {
            $mform->addElement(
                'static',
                'initialfile',
                get_string('initialfile', 'mod_eurooffice'),
                $this->get_initial_file_link()
            );
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepares the form data before rendering, including file area setup.
     *
     * Sets up a draft file area for the module's file manager and adds the draft item ID
     * to the form default values before passing them to the parent's set_data method.
     *
     * @param stdClass|array $defaultvalues The default values for the form fields
     * @return void
     */
    public function set_data($defaultvalues) {
        $defaultvalues = (object) $defaultvalues;

        $draftitemid = null;
        file_prepare_draft_area(
            $draftitemid,
            $this->context->id,
            'mod_eurooffice',
            filesystem::FILEAREA,
            0,
            filesystem::get_filemanager_opts()
        );
        $defaultvalues->file = $draftitemid;

        parent::set_data($defaultvalues);
    }
}
