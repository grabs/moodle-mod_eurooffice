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

use mod_eurooffice\api\config;
use mod_eurooffice\api\filesystem;
use advanced_testcase;
use context_module;
use stdClass;

/**
 * Test cases for the config class
 *
 * @package   mod_eurooffice
 * @author    Your Name
 * @copyright 2024 Your Institution
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class config_test extends advanced_testcase {
    /** @var stdClass The course object */
    protected $course;

    /** @var stdClass The module instance */
    protected $module;

    /** @var context_module The module context */
    protected $context;

    /** @var stdClass The test user */
    protected $user;

    /**
     * Set up the test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Create course, module and user.
        $this->course = $this->getDataGenerator()->create_course();
        $this->module = $this->getDataGenerator()->create_module(
            'eurooffice',
            [
                'course' => $this->course,
                'initialfiletype' => filesystem::FILETYPE_TEXT,
            ]
        );

        $this->context = context_module::instance($this->module->cmid);
        $this->user = $this->getDataGenerator()->create_user();
    }

    /**
     * Test the constructor with valid file
     * @covers \mod_eurooffice\api\config::__construct
     */
    public function test_constructor(): void {
        $this->setUser($this->user);

        // Test constructor.
        $config = new config($this->course->id, $this->context, $this->module);
        $this->assertInstanceOf(config::class, $config);
    }

    /**
     * Test get_config method with valid configuration
     * @covers \mod_eurooffice\api\config::get_config
     */
    public function test_get_config(): void {
        global $CFG;

        $this->setUser($this->user);

        // Set up plugin configuration.
        set_config('secret', 'testsecret', 'mod_eurooffice');
        set_config('url', 'https://example.com', 'mod_eurooffice');

        // Test get_config.
        $config = new config($this->course->id, $this->context, $this->module);
        $result = $config->get_config();

        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('api', $result);
        $this->assertStringContainsString('api.js', $result['api']);
        $this->assertArrayHasKey('token', $result['config']);
    }

    /**
     * Test get_config throws exception when secret is not set
     * @covers \mod_eurooffice\api\config::get_config
     */
    public function test_get_config_throws_exception_without_secret(): void {
        $this->setUser($this->user);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('secretnotset', 'mod_eurooffice'));

        $config = new config($this->course->id, $this->context, $this->module);
        $config->get_config();
    }

    /**
     * Test get_permissions method
     * @covers \mod_eurooffice\api\config::get_permissions
     */
    public function test_get_permissions(): void {
        $this->setUser($this->user);

        // Test with non-editing user.
        $config = new config($this->course->id, $this->context, $this->module);
        $permissions = $config->get_permissions();
        $this->assertFalse($permissions['edit']);
        $this->assertFalse($permissions['print']);

        // Test with editing teacher.
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $this->course->id, 'editingteacher');
        $this->setUser($teacher);

        $config = new config($this->course->id, $this->context, $this->module);
        $permissions = $config->get_permissions();
        $this->assertTrue($permissions['edit']);
        $this->assertTrue($permissions['print']);
    }

    /**
     * Test get_api_url method
     * @covers \mod_eurooffice\api\config::get_api_url
     */
    public function test_get_api_url(): void {
        $this->setUser($this->user);

        // Set up plugin configuration.
        set_config('url', 'https://example.com', 'mod_eurooffice');

        $config = new config($this->course->id, $this->context, $this->module);
        $url = $config->get_api_url();

        $this->assertEquals('https://example.com/web-apps/apps/api/documents/api.js', $url);
    }
}
