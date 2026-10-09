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
 * Callback script
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState
// phpcs:disable moodle.Files.RequireLogin.Missing

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $CFG;

$siteidentifier = $CFG->siteidentifier;
$userid = required_param('userid', PARAM_INT);
$token = optional_param('token', '', PARAM_RAW);
$action = optional_param('action', '', PARAM_RAW);
$requestbody = file_get_contents('php://input');

$api = new \mod_eurooffice\api\api($userid, $siteidentifier, $token, $action, $requestbody);
$api->handle_request();
