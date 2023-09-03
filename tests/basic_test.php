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
 * Unit tests for the language condition.
 *
 * @package   availability_language
 * @copyright 2022 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace availability_language;

use \availability_language\{condition, frontend};
use \core_availability\{tree, info_module, mock_info, mock_condition};

/**
 * Unit tests for the language condition.
 *
 * @package   availability_language
 * @copyright 2022 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \availability_language
 */
class basic_test extends \basic_testcase {

    /**
     * Tests the constructor including error conditions.
     * @covers \availability_language\condition
     */
    public function test_constructor() {
        // This works with no parameters.
        $structure = (object)[];
        $language = new condition($structure);
        $this->assertNotEmpty($language);

        // This works with custom made languages.
        $structure->id = 'en_ar';
        $language = new condition($structure);
        $this->assertNotEmpty($language);

        // Invalid ->id.
        $language = null;
        $structure->id = null;
        try {
            $language = new condition($structure);
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('Invalid ->id for language condition', $e->getMessage());
        }
        $structure->id = 12;
        try {
            $language = new condition($structure);
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('Invalid ->id for language condition', $e->getMessage());
        }
        $this->assertEquals(null, $language);
    }

    /**
     * Tests the save() function.
     * @covers \availability_language\condition
     */
    public function test_save() {
        $structure = (object)['id' => 'fr'];
        $cond = new condition($structure);
        $structure->type = 'language';
        $this->assertEqualsCanonicalizing($structure, $cond->save());
        $this->assertEqualsCanonicalizing((object)['type' => 'language', 'id' => 'nl'], $cond->get_json('nl'));
        $this->assertEqualsCanonicalizing((object)['type' => 'language', 'id' => 'en'], condition::get_json('en'));
        $this->assertEqualsCanonicalizing((object)['type' => 'language', 'id' => ''], condition::get_json(''));
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     * @covers \availability_language\condition
     */
    public function test_get_description() {
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
        $info = new mock_info();
        $language = new condition((object)['type' => 'language', 'id' => '']);
        $this->assertFalse($language->is_applied_to_user_lists());
        $this->assertEquals($language->get_description(false, false, $info), '');
        $language = new condition((object)['type' => 'language', 'id' => 'en']);
        $desc = $language->get_description(true, false, $info);
        $this->assertEquals('The student\'s language is English ‎(en)‎', $desc);
        $desc = $language->get_description(true, true, $info);
        $this->assertEquals('The student\'s language is not English ‎(en)‎', $desc);
        $desc = $language->get_standalone_description(true, false, $info);
        $this->assertStringContainsString('Not available unless: The student\'s language is English', $desc);
        $result = \phpunit_util::call_internal_method($language, 'get_debug_string', [], 'availability_language\condition');
        $this->assertEquals('en', $result);
    }
}
