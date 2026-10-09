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

use Firebase\JWT\JWT;
use mod_eurooffice\crypt;

/**
 * Api config class
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {
    /**
     * The plugin configuration
     * @var \stdClass
     */
    protected $mycfg;
    /**
     * The stored_file for the document editor
     * @var \stored_file
     */
    protected $file;
    /**
     * The crypt utility class
     * @var crypt
     */
    protected $crypt;

    /**
     * Constructor for the EuroOffice API configuration.
     *
     * Initializes the configuration object with course and module context,
     * sets up encryption, retrieves module configuration, and validates
     * the required file exists in the file storage area.
     *
     * @param int $courseid The course ID
     * @param \context $context The course module context
     * @param \stdClass $cm The course_module record
     * @throws \moodle_exception When no required file is found in the file storage area
     */
    public function __construct(
        /**
         * The course ID
         * @var int
         */
        protected int $courseid,
        /**
         * The course module context
         * @var \context
         */
        protected \context $context,
        /**
         * The course_module record
         * @var \stdClass
         */
        protected \stdClass $cm
    ) {
        global $DB, $CFG;

        $this->crypt = new crypt($CFG->siteidentifier);
        $this->mycfg = get_config('mod_eurooffice');

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $this->context->id,
            'mod_eurooffice',
            \mod_eurooffice\api\filesystem::FILEAREA,
            0,
            'id',
            false
        );

        if (!$files) {
            throw new \moodle_exception(
                'error_missing_file',
                'mod_eurooffice',
                new \moodle_url('/course/view.php', ['id' => $courseid])
            );
        }
        $this->file = reset($files);
    }

    // Build a configuration to initialize the editor object in javascript.
    // All elements must be convertable to json.
    /**
     * Build a configuration to initialize the editor object in javascript.
     * All elements must be convertable to json.
     *
     * @throws \moodle_exception
     * @return array{api: string, config: array}
     */
    public function get_config() {
        global $USER;

        // Get the file we want to edit.
        $filename = $this->file->get_filename();
        // Download identifier. Used by the document server, which want to load the document for processing.
        $filetokenparts = [
            'contextid' => $this->context->id,
            'pathnamehash' => $this->file->get_pathnamehash(),
        ];
        $filetoken = $this->crypt->get_hash($filetokenparts);
        $callbackurl = new \moodle_url('/mod/eurooffice/callback.php', ['userid' => $USER->id, 'token' => $filetoken]);

        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Set up infos to the document.
        $document = [];
        $documenturl = new \moodle_url($callbackurl, ['action' => 'getfile']);
        $document['url'] = $documenturl->out(false);
        $document['fileType'] = $filetype;
        $document['title'] = $filename;
        $document['key'] = $this->file->get_pathnamehash(); // The pathnamehash is unique so it can be perfectly used here.
        $document['permissions'] = $this->get_permissions();

        // Set up configuration for the document editor.
        $editorconfig = [];
        $callbackurl = new \moodle_url($callbackurl, ['action' => 'documentaction']);
        $editorconfig['callbackUrl'] = $callbackurl->out(false);

        // User infos as part of editor configuration.
        $user = [];
        $user['id'] = $USER->id;
        $user['name'] = fullname($USER);
        $userimageurl = new \moodle_url('/mod/eurooffice/userpic.php', ['id' => $USER->id, 'token' => $this->crypt->get_userid()]);
        $user['image'] = $userimageurl->out(false);
        $editorconfig['user'] = $user;

        // Customization to show the close button.
        // To make this work, a javascript callback function "onRequestClose" must be defined!
        $customization = [];
        $customization['close'] = [
            'visible' => true,
            'text' => 'Close',
        ];

        // Customization to prevent showing tooltips for new features!
        $customization['features'] = [
            'featuresTips' => false,
        ];

        // Some customizations that may later be made configurable.
        $customization['forcesave'] = true;
        $customization['commentAuthorOnly'] = true;
        $customization['logo'] = [
            'visible' => false,
        ];

        // Add customization to editor config.
        $editorconfig['customization'] = $customization;

        // Set current user language as language for the editor.
        $editorconfig['lang'] = current_language();

        // For a proper presentation, we need to know if the user is on a mobile device.
        $devicetype = \core_useragent::get_device_type();
        if ($devicetype == 'tablet' || $devicetype == 'mobile') {
            $devicetype = 'mobile';
        } else {
            $devicetype = 'desktop';
        }

        // Add all configuration infos into an array before we build a jwt token from it.
        $config = [
            'type' => $devicetype,
            'document' => $document,
            'editorConfig' => $editorconfig,
        ];

        // Create the jwt token from the configuration and add it to it as separate key.
        // For this the secret is needed.
        if (empty($this->mycfg->secret)) {
            throw new \moodle_exception(
                'secretnotset',
                'mod_eurooffice',
                new \moodle_url('/course/view.php', ['id' => $this->courseid])
            );
        }
        $token = JWT::encode($config, $this->mycfg->secret, 'HS256');
        $config['token'] = $token;

        return [
            'config' => $config,
            'api' => $this->get_api_url(),
        ];
    }

    /**
     * Get the url to load the API javascript from the document server.
     * @return string
     */
    public function get_api_url() {
        return $this->mycfg->url . '/web-apps/apps/api/documents/api.js';
    }

    /**
     * Retrieves the available permissions for the current user in the EuroOffice module.
     *
     * Determines which actions (print, download, edit, review) the user is allowed to perform
     * based on their capability to edit in the current context.
     * Currently all permissions are handled the same way, but this can be adjusted later.
     *
     * @return array An associative array of permissions where keys are permission names
     *               (print, download, edit, review) and values are boolean indicators
     *               of whether the permission is granted.
     */
    public function get_permissions() {
        $eurofficepermissions = ['print', 'download', 'edit', 'review'];
        $permissions = [];
        $canedit = has_capability('mod/eurooffice:edit', $this->context);
        foreach ($eurofficepermissions as $perm) {
            $permissions[$perm] = $canedit;
        }

        return $permissions;
    }
}
