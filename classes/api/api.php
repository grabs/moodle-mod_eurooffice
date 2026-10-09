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
class api {
    /** Status "editing" */
    public const TRACKERSTATUS_EDITING = 1;
    /** Status "mustsave" */
    public const TRACKERSTATUS_MUSTSAVE = 2;
    /** Status "corrupted" */
    public const TRACKERSTATUS_CORRUPTED = 3;
    /** Status "closed" */
    public const TRACKERSTATUS_CLOSED = 4;
    /** Status "forcesave" */
    public const TRACKERSTATUS_FORCESAVE = 6;
    /** Status "corrupedforcesave" */
    public const TRACKERSTATUS_CORRUPTEDFORCESAVE = 7;

    /** The default value for connecttimeout */
    public const DEFAULT_CONNECTTIMEOUT = 300;
    /** The default value for timeout */
    public const DEFAULT_TIMEOUT = 2;

    /** Get file action */
    public const ACTION_GETFILE = 'getfile';
    /** Document actions */
    public const ACTION_DOCUMENTACTION = 'documentaction';

    /** @var \mod_eurooffice\crypt */
    protected $crypt;
    /** @var array */
    protected $requestdata;
    /** @var \stdClass */
    protected $tokencontent;
    /** @var string */
    protected $error;

    /**
     * Tests the connection to a given URL.
     *
     * This method attempts to establish a connection to the specified URL and checks
     * if the response status code is 200 (OK). It uses cURL with default timeout settings.
     *
     * @param string $url The URL to test the connection against.
     * @return bool Returns true if the connection is successful (HTTP 200), false otherwise.
     */
    public static function test_connection($url): bool {
        // Return false immediately if the provided URL is empty.
        if (empty($url)) {
            return false;
        }

        // Initialize a new cURL handle for the request.
        $ch = new \curl();

        // Set the cURL options with default timeout values.
        $curloptions = [];
        $curloptions['CURLOPT_CONNECTTIMEOUT'] = static::DEFAULT_CONNECTTIMEOUT;
        $curloptions['CURLOPT_TIMEOUT']        = static::DEFAULT_TIMEOUT;

        // Execute a GET request to the specified URL with the configured options.
        $ch->get($url, [], $curloptions);

        // Retrieve the request information, including the HTTP status code.
        $info = $ch->get_info();

        // Return true if the HTTP status code is 200, indicating a successful connection.
        return $info['http_code'] == 200;
    }

    /**
     * Constructor for the API class.
     *
     * Validates the required parameters and initializes the cryptographic handler.
     * Decodes the token and request body for further processing.
     *
     * @param int $userid The user ID making the API request.
     * @param string $siteidentifier The site identifier for cryptographic operations.
     * @param string $token The authentication token provided in the request.
     * @param string $action The API action to be performed.
     * @param string|false $requestbody The JSON-encoded request body.
     */
    public function __construct(
        /**
         * The user ID for the API request
         * @var int
         */
        protected $userid,
        /**
         * The site identifier for the API request
         * @var string
         */
        protected $siteidentifier,
        /**
         * The authentication token for the API request
         * @var string
         */
        protected $token,
        /**
         * The action to be performed via the API
         * @var string
         */
        protected $action,
        /**
         * The request body data for the API call
         * @var array|object
         */
        protected $requestbody
    ) {
        // Validate that the action parameter is not empty.
        if (empty($this->action)) {
            $this->send_response(new json_response(['error' => 1], 500));
        }

        // Validate that the token parameter is not empty.
        if (empty($this->token)) {
            $this->send_response(new json_response(['error' => 1], 500));
        }
        $this->crypt = new \mod_eurooffice\crypt($siteidentifier);
        [$this->tokencontent, $this->error] = $this->crypt->read_hash($this->token);
        if ($this->error || $this->tokencontent === null) {
            $this->send_response(new json_response(['error' => 1], 500));
        }

        // Validate that the request body is not false.
        if ($this->requestbody === false) {
            $this->send_response(new json_response(['error' => 1], 500));
        }

        // Decode the JSON request body into an associative array.
        $this->requestdata = json_decode($this->requestbody, true);
    }

    /**
     * Handles incoming API requests based on the action parameter.
     *
     * This method routes the request to the appropriate handler method
     * or returns an error response if the action is invalid or required
     * parameters are missing.
     */
    public function handle_request() {
        // Handle file retrieval requests by sending the requested document.
        if ($this->action == static::ACTION_GETFILE) {
            $this->send_document();
        }

        // Process document action requests if valid parameters are provided.
        if ($this->action == static::ACTION_DOCUMENTACTION) {
            if ($this->requestdata === null) {
                $this->send_response(new json_response(['error' => 1], 500));
            }
            if (!isset($this->requestdata['status'])) {
                $this->send_response(new json_response(['error' => 1], 500));
            }
            $this->handle_document_action();
        }

        // Send an error response for any unhandled or invalid actions.
        $this->send_response(new json_response(['error' => 1], 500));
    }

    /**
     * Handles document actions based on the status received from the request.
     *
     * This method processes different document statuses and performs corresponding
     * actions such as saving documents or sending appropriate responses.
     */
    protected function handle_document_action() {
        $status = (int) $this->requestdata['status'];
        switch ($status) {
            case static::TRACKERSTATUS_EDITING:
            case static::TRACKERSTATUS_CLOSED:
                // Send a success response for editing or closed statuses.
                $this->send_response(new json_response(['error' => 0], 200));
                break;

            case static::TRACKERSTATUS_MUSTSAVE:
            case static::TRACKERSTATUS_FORCESAVE:
                // Try to save the document to Moodle.
                if ($this->save_document_to_moodle()) {
                    $this->send_response(new json_response(['error' => 0], 200));
                } else {
                    // Send an error response if the document could not be saved.
                    $this->send_response(new json_response(['error' => 1], 500));
                }
                break;

            case static::TRACKERSTATUS_CORRUPTED:
            case static::TRACKERSTATUS_CORRUPTEDFORCESAVE:
                // Send an error response for corrupted document statuses.
                $this->send_response(new json_response(['error' => 1], 500));
                break;

            default:
                // Send an error response for any unhandled status.
                $this->send_response(new json_response(['error' => 1], 500));
        }
    }

    /**
     * Saves a document from EuroOffice to Moodle by downloading it from the provided URL.
     *
     * This method checks for existing file permissions before replacing the content.
     * Only users with appropriate capabilities can update the document.
     *
     * @return bool Returns true if the document was successfully saved, false otherwise.
     */
    protected function save_document_to_moodle() {
        // Get the download URL from the request data.
        $downloadurl = $this->requestdata['url'];

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash($this->tokencontent->pathnamehash)) {
            return false;
        }

        // Verify that the file's context matches the token's context.
        if ($file->get_contextid() != $this->tokencontent->contextid) {
            return false;
        }

        // Check if the current user has permission to edit this file.
        $canedit = has_any_capability(
            [
                'moodle/course:manageactivities',
                'mod/eurooffice:edit',
            ],
            \context::instance_by_id($file->get_contextid()),
            $this->userid
        );
        if (!$canedit) {
            return false;
        }

        // Download the file content from the provided URL.
        try {
            $tempfile = tempnam(make_temp_directory('/mod_eurooffice'), 'tmp');
            $fileloaded = download_file_content(
                url: $downloadurl,
                tofile: $tempfile
            );
        } catch (\moodle_exception $e) {
            // Return false if any error occurs during the download or file operations.
            return false;
        }

        if ($fileloaded) {
            // Prepare file record for the new file creation.
            $filerec = [
                'contextid'   => $file->get_contextid(),
                'component'   => $file->get_component(),
                'filearea'    => $file->get_filearea(),
                'itemid'      => $file->get_itemid(),
                'filename'    => $file->get_filename(),
                'filepath'    => $file->get_filepath(),
                'userid'      => $file->get_userid(),
                'timecreated' => $file->get_timecreated(),
            ];

            // Delete the existing file before creating a new one with updated content.
            $file->delete();
            if ($fs->create_file_from_pathname($filerec, $tempfile)) {
                unlink($tempfile);
            }
            return true;
        }
        // Return false if any condition fails.
        return false;
    }

    /**
     * Sends the stored document file to the client.
     *
     * Checks if the requesting user has edit capabilities before sending the file.
     * If the user has edit permissions, the file is sent with forced download.
     * The file is retrieved using the stored pathname hash from the token content.
     *
     * @throws \file_exception If the file cannot be found
     */
    protected function send_document() {
        $context = \context::instance_by_id($this->tokencontent->contextid);
        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash($this->tokencontent->pathnamehash)) {
            send_file_not_found();
        }
        $canedit = has_any_capability(
            [
                'moodle/course:manageactivities',
                'mod/eurooffice:edit',
            ],
            $context,
            $this->userid
        );

        if ($canedit) {
            send_stored_file($file, null, 0, true);
        }
    }

    /**
     * Send the full response including the headers
     * @param json_response $response
     * @return void
     */
    protected function send_response(json_response $response) {
        $response->send_headers();
        $response->out();
    }
}
