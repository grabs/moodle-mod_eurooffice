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

use mod_eurooffice\crypt;
use mod_eurooffice\util;

/**
 * User info class
 * This class provides methods to retrieve user information to show the user pictures as avatar in the editor.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userinfo {
    /** @var \stdClass Configuration settings for the EuroOffice module. */
    protected $mycfg;
    /** @var \stdClass The course record associated with the current context. */
    protected $course;
    /** @var crypt Encryption utility for generating secure tokens. */
    protected $crypt;

    /**
     * Constructor for the userinfo class.
     *
     * @param array $userids Array of user IDs to fetch information for.
     * @param \stdClass $cm The course module object.
     */
    public function __construct(
        /**
         * Array of user IDs to process
         * @var array
         */
        protected array $userids,
        /**
         * Course module object
         * @var \stdClass
         */
        protected \stdClass $cm
    ) {
        global $DB, $CFG;

        // Load module configuration settings.
        $this->mycfg = get_config('mod_eurooffice');
        // Fetch the course record associated with the current course module.
        $this->course = $DB->get_record('course', ['id' => $cm->course]);
        // Initialize the encryption utility with the site identifier.
        $this->crypt = new crypt(util::site_secret());
    }

    /**
     * Retrieves user information for the provided user IDs.
     *
     * Only includes users that the current user is allowed to view.
     * Returns an array of user information including ID, image URL, and full name.
     *
     * @return array Array of user information arrays.
     */
    public function get_infos() {
        // Return empty array if no user IDs are provided or if the input is invalid.
        if (empty($this->userids) || !is_array($this->userids)) {
            return [];
        }

        $infos = [];

        // Process each user ID to gather information.
        foreach ($this->userids as $userid) {
            $user = \core_user::get_user($userid);
            // Check if the current user has permission to view this profile and so the userpicture.
            if (\core\user::can_view_profile($user, $this->course)) {
                // Generate a secure URL for the user's profile picture.
                $userimageurl = new \moodle_url(
                    '/mod/eurooffice/userpic.php',
                    [
                        'token' => $this->crypt->get_userid($user->id),
                    ]
                );
                $info = [
                    'id' => $user->id,
                    'image' => $userimageurl->out(false),
                    'name' => fullname($user),
                ];
                $infos[] = $info;
            }
        }
        return $infos;
    }
}
