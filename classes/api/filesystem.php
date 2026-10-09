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

namespace mod_eurooffice\api;

/**
 * Editor class
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filesystem {
    /**
     * The filearea use for stored_file objects.
     */
    public const FILEAREA = 'document';
    /**
     * The filearea use for stored_file objects.
     */
    public const FILEAREA_INITIAL = 'initial';

    /** Define the eurooffice initialfiletype for individual files */
    public const FILETYPE_UPLOAD = 'upload';
    /** Define the eurooffice initialfiletype as simple text */
    public const FILETYPE_TEXT = 'textonly';
    /** Define the eurooffice initialfiletype as spreadsheet */
    public const FILETYPE_SPREADSHEET = 'spreadsheet';
    /** Define the eurooffice initialfiletype as wordprocessor */
    public const FILETYPE_WORDPROCESSOR = 'wordprocessor';
    /** Define the eurooffice initialfiletype as presentation */
    public const FILETYPE_PRESENTATION = 'presentation';

    /**
     * Get an array which defines all accepted file types, this activity can handle.
     *
     * @return array
     */
    public static function get_accepted_types() {
        return [
            // Plain text.
            '.txt',
            // Textprocesser files.
            '.rtf',
            '.doc',
            '.docx',
            '.odt',
            // Spreadsheet files.
            '.xls',
            '.xlsx',
            '.ods',
            // Presentation files.
            '.ppt',
            '.pptx',
            '.odp',
            '.odg',
        ];
    }

    /**
     * Get options for the filemanger.
     *
     * @return array
     */
    public static function get_filemanager_opts() {
        return [
            'subdirs'        => 0,
            'maxbytes'       => 0,
            'maxfiles'       => 1,
            'accepted_types' => static::get_accepted_types(),
        ];
    }

    /**
     * Stores the initial file for a eurooffice Online activity.
     *
     * This method is responsible for handling the storage of the initial file for a eurooffice Online activity,
     * based on the activity's initialfiletype setting. It supports four different formats:
     * - upload,
     * - textonly,
     * - wordprocessor
     * - spreadsheet
     * - presentation
     *
     * @param \stdClass $eurooffice The eurooffice Online activity object.
     * @param int $moduleid The ID of the module instance.
     * @return bool True if the initial file was successfully stored, false otherwise.
     */
    public static function store_initial_file($eurooffice, $moduleid): bool {
        $context = \context_module::instance($moduleid);
        $fs      = get_file_storage();

        // Store the initial file from the uploaded file.
        if ($eurooffice->initialfiletype == static::FILETYPE_UPLOAD) {
            try {
                file_postupdate_standard_filemanager(
                    $eurooffice,
                    'initialfile',
                    static::get_filemanager_opts(),
                    $context,
                    'mod_eurooffice',
                    static::FILEAREA_INITIAL,
                    0
                );
                return true;
            } catch (\moodle_exception $e) {
                return false;
            }
        }

        // The common filerecord for the other initial files.
        $filerec = (object) [
            'contextid' => $context->id,
            'component' => 'mod_eurooffice',
            'filearea'  => static::FILEAREA_INITIAL,
            'itemid'    => 0,
            'filepath'  => '/',
        ];

        // If the initialfiletype is different to "upload", we use a blank file with the given file type.
        $templates = static::get_templates();
        if ($filepath = ($templates[$eurooffice->initialfiletype] ?? '')) {
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $filerec->filename = clean_filename(format_string($eurooffice->name)) . '.' . $ext;
            if ($fs->create_file_from_pathname($filerec, $filepath)) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Create the file the user will work on from the initial file.
     * @param int $cmid
     * @return \stored_file
     */
    public static function store_file_from_initial(int $cmid) {
        $context = \context_module::instance($cmid);
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $context->id, // Param contextid.
            'mod_eurooffice', // Param component.
            static::FILEAREA_INITIAL, // Param filearea.
            false, // Param itemid.
            '', // Param sort.
            false // Param includedirs.
        );
        $file = reset($files);
        if (!$file) {
            throw new \moodle_exception('missingfile', 'mod_eurooffice');
        }

        $filerec = (object) [
            'contextid' => $context->id,
            'component' => 'mod_eurooffice',
            'filearea'  => static::FILEAREA,
            'itemid'    => 0,
            'filepath'  => '/',
        ];
        $fs->delete_area_files($context->id, 'mod_eurooffice', static::FILEAREA);
        return $fs->create_file_from_storedfile($filerec, $file);
    }

    /**
     * Choose an appropriate filetype icon based on the mimetype of the given stored_file.
     * @param \stored_file $file
     * @return string icon URL to be used in `cached_cm_info` or false if there is no appropriate icon
     */
    public static function get_module_icon(\stored_file $file) {
        $mimetype = $file->get_mimetype();
        switch ($mimetype) {
            case 'application/vnd.oasis.opendocument.text': // ODT.
            case 'application/msword': // DOC.
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // DOCX.
            case 'text/rtf': // RTF.
                return 'textprocessing';
            case 'application/vnd.oasis.opendocument.spreadsheet': // ODS.
            case 'application/vnd.ms-excel': // XLS.
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // XLSX.
                return 'calc';
            case 'application/vnd.oasis.opendocument.presentation': // ODP.
            case 'application/vnd.ms-powerpoint': // PPT.
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': // PPTX.
                return 'presentation';
            case 'text/plain': // Text.
                return 'text';

            default:
                return 'unknown';
        }

        return false;
    }

    /**
     * The menu to choose one of the initialfiletypes.
     *
     * @return array
     */
    public static function format_menu() {
        return [
            static::FILETYPE_SPREADSHEET   => get_string('template_' . static::FILETYPE_SPREADSHEET, 'mod_eurooffice'),
            static::FILETYPE_WORDPROCESSOR => get_string('template_' . static::FILETYPE_WORDPROCESSOR, 'mod_eurooffice'),
            static::FILETYPE_PRESENTATION  => get_string('template_' . static::FILETYPE_PRESENTATION, 'mod_eurooffice'),
            static::FILETYPE_TEXT          => get_string('template_' . static::FILETYPE_TEXT, 'mod_eurooffice'),
            static::FILETYPE_UPLOAD        => get_string('template_' . static::FILETYPE_UPLOAD, 'mod_eurooffice'),
        ];
    }

    /**
     * Returns a list of legacy templates.
     * This method returns an array of templates that can be used as initial file for eurooffice Online activities.
     * The keys of the array are the initialfiletype IDs and the values are the file paths of the templates.
     *
     * @return array List of legacy templates.
     */
    public static function get_templates() {
        global $CFG;
        return [
            static::FILETYPE_TEXT          => $CFG->dirroot . '/mod/eurooffice/blankfiles/blanktextonly.txt',
            static::FILETYPE_SPREADSHEET   => $CFG->dirroot . '/mod/eurooffice/blankfiles/blankspreadsheet.ods',
            static::FILETYPE_WORDPROCESSOR => $CFG->dirroot . '/mod/eurooffice/blankfiles/blankwordprocessor.odt',
            static::FILETYPE_PRESENTATION  => $CFG->dirroot . '/mod/eurooffice/blankfiles/blankpresentation.odp',
        ];
    }
}
