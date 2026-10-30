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
 * Initializes the EuroOffice editor with the given configuration.
 *
 * @module    mod_eurooffice/eurooffice_editor
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2026 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import notification from 'core/notification';
import log from 'core/log';

/**
 * Initializes the EuroOffice editor with the given configuration.
 *
 * @param {integer} contextid - The contextid of the module instance
 * @param {integer} cmid - The course module id
 * @param {string} courseUrl - URL to redirect to when closing the editor.
 * @returns {Promise<void>} Resolves when the editor is initialized.
 */
export async function init(contextid, cmid, courseUrl) {
    /**
     * Loads the document editing API script asynchronously.
     * @returns {Promise<void>} Resolves when the script is loaded.
     */
    function loadApi() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = editorConfig.api;
            script.async = true;
            script.onload = resolve;
            script.onerror = () => reject(new Error('Could not load api: ' + editorConfig.api));
            document.head.appendChild(script);
        });
    }

    const configLoader = await import('mod_eurooffice/configloader');
    const userInfoLoader = await import('mod_eurooffice/userinfoloader');
    const str = await import('core/str');
    const editorConfig = await configLoader.getEditorConfig(contextid);

    try {
        await loadApi();
    } catch (error) {
        log.debug(error.message);
    }

    // Check if the document API is available.
    if (!window.hasOwnProperty('DocsAPI')) {
        var title = await str.get_string('error');
        var msg = await str.get_string('error_connecting', 'mod_eurooffice');
        notification.alert(title, msg);
        return;
    }

    let docEditor;

    /**
     * Callback function for the close button in the editor.
     */
    function onRequestClose() {
        // Destroy the editor instance and redirect to course page.
        docEditor.destroyEditor();
        document.location.href = courseUrl;
    }

    /**
     * Get user infos to show the avatar icon in the userlist of the editor.
     * @param {Object} event
     */
    async function onRequestUsers(event) {
        const c = event.data.c;
        const userids = event.data.id;
        if (!Array.isArray(userids)) {
            return;
        }
        const userInfo = await userInfoLoader.getUserInfos(userids, contextid);

        docEditor.setUsers({
            c: c,
            users: userInfo
        });
    }

    let docconfig = editorConfig.config;
    docconfig.events = {onRequestClose, onRequestUsers};
    if (window.hasOwnProperty('DocsAPI')) {
        docEditor = new window.DocsAPI.DocEditor('eurooffice-editor-' + cmid, docconfig);
    }
}
