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
 * Json response class to generate and send json formated content.
 *
 * @package   mod_eurooffice
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class json_response {
    /**
     * Status codes translation table.
     *
     * The list of codes is complete according to the
     * {@link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml}
     * (last updated 2021-10-01).
     *
     * Unless otherwise noted, the status code is defined in RFC2616.
     */
    public const STATUS_TEXTS = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing', // RFC2518.
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status', // RFC4918.
        208 => 'Already Reported', // RFC5842.
        226 => 'IM Used', // RFC3229.
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect', // RFC7238.
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large', // RFC-ietf-httpbis-semantics.
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // RFC2324.
        421 => 'Misdirected Request', // RFC7540.
        422 => 'Unprocessable Content', // RFC-ietf-httpbis-semantics.
        423 => 'Locked', // RFC4918.
        424 => 'Failed Dependency', // RFC4918.
        425 => 'Too Early', // RFC-ietf-httpbis-replay-04.
        426 => 'Upgrade Required', // RFC2817.
        428 => 'Precondition Required', // RFC6585.
        429 => 'Too Many Requests', // RFC6585.
        431 => 'Request Header Fields Too Large', // RFC6585.
        451 => 'Unavailable For Legal Reasons', // RFC7725.
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates', // RFC2295.
        507 => 'Insufficient Storage', // RFC4918.
        508 => 'Loop Detected', // RFC5842.
        510 => 'Not Extended', // RFC2774.
        511 => 'Network Authentication Required', // RFC6585.
    ];

    /**
     * Constructor for the JSON response.
     *
     * @param mixed $data The data to be included in the JSON response
     * @param int $status HTTP status code for the response (default: 200)
     */
    public function __construct(
        /**
         * Data to be json encoded
         * @var mixed
         */
        protected $data,
        /**
         * Status code
         * @var int
         */
        protected int $status = 200
    ) {
    }

    /**
     * Sends HTTP headers for the JSON response.
     *
     * Sets the appropriate HTTP status code and status text based on the response's
     * status property, and sets the Content-Type header to application/json.
     *
     * @return void
     */
    public function send_headers() {
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        $statustext = static::STATUS_TEXTS[$this->status] ?? 'Unknown';
        header($protocol . ' ' . $this->status . ' ' . $statustext);
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Outputs the JSON-encoded data and optionally terminates script execution.
     *
     * @param bool $dieafter Whether to terminate script execution after output (default: true)
     * @return void
     */
    public function out($dieafter = true) {
        echo json_encode($this->data);
        if ($dieafter) {
            die;
        }
    }
}
