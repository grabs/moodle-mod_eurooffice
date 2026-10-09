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
 * Delivery of userpictures to show in eurooffice as avatar.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This script is called by the eurooffice server and does not need cookies!
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$userid = required_param('id', PARAM_INT);
$token = required_param('token', PARAM_TEXT);

$crypt = new \mod_eurooffice\crypt($CFG->siteidentifier);
$hash = $crypt->get_userid($userid);
$defaultimage = $CFG->dirroot . '/pix/u/f1.png';

if ($token != $hash) {
    send_file_not_found();
}

$context = \context_user::instance($userid);
$fs = get_file_storage();
if (!$file = $fs->get_file($context->id, 'user', 'icon', 0, '/', 'f1.jpg')) {
    if (!$file = $fs->get_file($context->id, 'user', 'icon', 0, '/', 'f1.png')) {
        send_file($defaultimage, 'f1.png');
    }
}

if (!empty($file)) {
    send_stored_file($file);
} else {
    $defaultimage = $CFG->wwwroot . '/pix/u/f1.png';
    send_file($defaultimage, 'f1.png');
}
