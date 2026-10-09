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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Crypt utility
 * This class is inspired by the euroffice-nextcloud plugin.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crypt {
    /**
     * Algorithm to use for encoding the JWT token
     * @var string
     */
    public const DEFAULT_ALGORITHM = 'HS256';

    /**
     * The unique siteidentifier for the hash function.
     *
     * @var string
     */
    private $siteidentifier;

    /**
     * The hash algorithm
     *
     * @var string
     */
    private $algorithm;

    /**
     * Constructor for the crypt class.
     *
     * @param string $siteidentifier The unique site identifier for hashing
     * @param string|null $algorithm The algorithm to use for JWT (defaults to HS256)
     */
    public function __construct($siteidentifier, $algorithm = null) {
        $this->siteidentifier = $siteidentifier;

        if (!empty($alg)) {
            $this->algorithm = $algorithm;
        } else {
            $this->algorithm = static::DEFAULT_ALGORITHM;
        }
    }

    /**
     * Generates a JWT hash for the given object.
     *
     * @param mixed $object The data to be encoded in the JWT
     * @return string The generated JWT token
     * @throws \moodle_exception If the secret key is too short
     */
    public function get_hash($object) {
        try {
            return JWT::encode($object, $this->siteidentifier, $this->algorithm);
        } catch (\Exception $e) {
            throw new \moodle_exception('JWT secret key is too short (minimum 32 characters required).', 0, $e);
        }
    }

    /**
     * Decodes and verifies a JWT hash.
     *
     * @param string $hash The JWT token to decode
     * @return array An array containing the decoded result and any error message
     *               Format: [$result, $error]
     */
    public function read_hash($hash) {
        $result = null;
        $error = null;
        if ($hash === '') {
            return [$result, 'token is empty'];
        }
        try {
            $result = JWT::decode($hash, new Key($this->siteidentifier, $this->algorithm));
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        return [$result, $error];
    }

    /**
     * Generates a unique user identifier hash.
     *
     * @param int|false $userid The user ID to generate hash for (defaults to current user)
     * @return string The generated user hash
     */
    public function get_userid($userid = false) {
        global $USER, $DB;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $mycfg = get_config('mod_eurooffice');
        $user = $DB->get_record('user', ['id' => $userid], 'id, username, firstname, lastname, email');
        $userstr = json_encode($user);
        $salt = $mycfg->secret;
        return sha1(json_encode($userstr . $salt));
    }
}
