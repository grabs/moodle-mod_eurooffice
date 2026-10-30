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
 * Settings page
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_eurooffice\api\filesystem;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings->add(
        new admin_setting_configtext(
            'mod_eurooffice/url',
            get_string('euroofficeurl', 'mod_eurooffice'),
            get_string('euroofficeurl_help', 'mod_eurooffice'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'mod_eurooffice/secret',
            get_string('secret', 'mod_eurooffice'),
            get_string('secret_help', 'mod_eurooffice'),
            ''
        )
    );

    $helpcontent = $OUTPUT->render_from_template(
        'mod_eurooffice/explicit_warning',
        [
            'text' => get_string('allowcollaboraserverexplicit_help', 'mod_eurooffice'),
            'level' => 'danger',
        ]
    );
    $yesnooptions = [
        0 => get_string('no'),
        1 => get_string('yes'),
    ];
    $settings->add(
        new \admin_setting_configselect(
            'mod_eurooffice/alloweuroofficeserverexplicit',
            get_string('allowcollaboraserverexplicit', 'mod_eurooffice'),
            $helpcontent,
            0,
            $yesnooptions
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'mod_eurooffice/defaultfiletype',
            get_string('defaultfiletype', 'mod_eurooffice'),
            '',
            filesystem::FILETYPE_SPREADSHEET,
            filesystem::format_menu()
        )
    );
}
