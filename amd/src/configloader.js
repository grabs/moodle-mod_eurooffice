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
 * Get a configuration object for document editor.
 *
 * @module    mod_eurooffice/postmessage
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import fragment from 'core/fragment';
import notification from 'core/notification';

/**
 * Get a configuration object for document editor.
 *
 * @param {Integer} contextid
 * @returns {object} The document editor configuration object
 */
export const getEditorConfig = (contextid) => {
    const serviceparams = {
        contextid: contextid
    };
    var fragmentpromise = fragment.loadFragment('mod_eurooffice', 'get_editor_config', contextid, serviceparams);
    return fragmentpromise.then(function(editorConfig) {
        return JSON.parse(editorConfig);
    }).fail(notification.exception);
};
