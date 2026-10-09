# ![moodle-mod_eurooffice](pix/icon.png) Activity Module: Euro-Office Online integration for Moodle

Euro-Office is an open-source, browser-based office suite that lets multiple users edit documents, spreadsheets, presentations and drawings together in real time.

###### Key capabilities include:

- Real-time collaborative editing with comments and change tracking, so teams can work on the same file simultaneously.
- Broad file format support (ODF, modern and older Microsoft Office formats), preserving layout and formatting as much as possible.

This activity module enables Moodle users to create documents (simple text files, word, spreadsheet and presentation documents or upload a document) via a selfhosted Euro-Office document server i.e. [CODE](https://github.com/Euro-Office/DocumentServer) and work collaboratively on this documents.

This plugin is written by [Andreas Grabs](https://github.com/grabs) from Grabs EDV-Beratung.

## Requirements
- Euro-Office document server (Version 9.3.4 or later) and Moodle Server (Version 5.0 or later).

## Tested Versions
- Euro-Office document server: 9.3.4
- Moodle: 5.0
- Moodle: 5.1
- Moodle: 5.2
- Moodle: 5.3

## Installation
Copy all files into the folder **mod/eurooffice** inside your moodle installation. Run the installation process in moodle.

## Administrative Settings of the activity module
- the Euro-Office url (the url of the Euro-Office document server)
- the secret (The secret defined on the Euro-Office document server is needed for authentication)
- the default file type (such as Wordprocessor, Spreadsheet, Presentation and Simple text)

## Use of Euro-Office trademarks

The name "Euro-Office" is used to indicate that the plugin provides an integration facility for use of Euro-Office document server from within Moodle.
The name does not imply an endorsement by Euro-Office, nor does it indicate who develops and provides the plugin.
This plugin was created and is offered by members of the community.
