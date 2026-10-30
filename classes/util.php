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

namespace mod_eurooffice;

/**
 * Utility class providing helper methods for the EuroOffice module.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Retrieves or generates the site secret key for the EuroOffice module.
     *
     * If no secret exists in the module configuration, a new 100-character
     * random string is generated and stored as the site secret.
     *
     * @return string The site secret key
     */
    public static function site_secret() {
        static $secret;
        if (empty($secret)) {
            $secret = get_config('mod_eurooffice', 'sitesecret');
            if (empty($secret)) {
                $secret = random_string(100);
                set_config('sitesecret', $secret, 'mod_eurooffice');
            }
        }
        return $secret;
    }
}
