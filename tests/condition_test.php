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
 * @package availability_language
 * @copyright 2017 eWallah.net (info@eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_language\condition;

/**
 * Unit tests for the language condition.
 *
 * @package availability_language
 * @copyright 2017 eWallah.net (info@eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testcase extends advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_condition.php');
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using language condition as part of tree.
     */
    public function test_in_tree() {
        global $CFG, $SESSION, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course with language turned on and a Page.
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course([]);
        $info = new \core_availability\mock_info($course, $USER->id);

        $arr1 = ['type' => 'language', 'id' => 'en'];
        $arr2 = ['type' => 'language', 'id' => 'fr'];
        $structure1 = (object)['op' => '|', 'show' => true, 'c' => [(object)$arr1]];
        $structure2 = (object)['op' => '|', 'show' => true, 'c' => [(object)$arr2]];
        $tree1 = new \core_availability\tree($structure1);
        $tree2 = new \core_availability\tree($structure2);

        // Initial check.
        $result1 = $tree1->check_available(false, $info, true, $USER->id);
        $result2 = $tree2->check_available(false, $info, true, $USER->id);
        $this->assertTrue($result1->is_available());
        $this->assertFalse($result2->is_available());

        // Change language.
        $SESSION->lang = 'fr';
        $result1 = $tree1->check_available(false, $info, true, $USER->id);
        $result2 = $tree2->check_available(false, $info, true, $USER->id);
        $this->assertFalse($result1->is_available());
        $this->assertTrue($result2->is_available());
    }

    /**
     * Tests the constructor including error conditions.
     */
    public function test_constructor() {
        // This works with no parameters.
        $structure = (object)[];
        $language = new condition($structure);

        // This works with custom made languages.
        $structure->id = 'en_ar';
        $language = new condition($structure);

        // Invalid ->id.
        $structure->id = null;
        try {
            $language = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Invalid ->id for language condition', $e->getMessage());
        }
        $structure->id = 12;
        try {
            $language = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Invalid ->id for language condition', $e->getMessage());
        }
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)['id' => 'fr'];
        $cond = new condition($structure);
        $structure->type = 'language';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     */
    public function test_get_description() {
        $info = new \core_availability\mock_info();
        $language = new condition((object)['type' => 'language', 'id' => 'en']);
        $desc = $language->get_description(true, false, $info);
        $this->assertEquals($desc, 'The student\'s language is English ‎(en)‎');
        $desc = $language->get_description(true, true, $info);
        $this->assertEquals($desc, 'The student\'s language is not English ‎(en)‎');
        $this->assertContains('language is English', $language->get_standalone_description(false, false, $info));
        $this->assertContains('language is not English', $language->get_standalone_description(false, true, $info));
    }

    /**
     * Tests using language condition in front end.
     */
    public function test_frontend() {
        global $CFG, $DB, $PAGE, $SESSION, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $page = $generator->get_plugin_generator('mod_page')->create_instance(['course' => $course]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($page->cmid);
        $PAGE->set_url('/course/modedit.php', ['update' => $page->cmid]);
        \core_availability\frontend::include_all_javascript($course, $cm);
        $this->setuser($user);
        $info = new \core_availability\info_module($cm);
        $cond = new condition((object)['type' => 'language', 'id' => 'en']);
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(false, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        // Change language.
        $SESSION->lang = 'fr';
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(false, $info, false, $user->id));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $this->setAdminUser();
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(false, $info, false, $user->id));
        $this->assertTrue($cond->is_available(false, $info, true, $USER->id));
        $this->assertTrue($cond->is_available(false, $info, false, $USER->id));
        // No language.
        $cond = new condition((object)['type' => 'language', 'id' => '']);
        $this->assertTrue($cond->is_available(false, $info, false, $USER->id));
        // No id.
        $cond = new condition((object)['type' => 'language']);
        $this->assertTrue($cond->is_available(false, $info, false, $USER->id));
        $this->assertFalse($cond->is_available_for_all());
        $this->assertFalse($cond->update_dependency_id(null, 1, 2));
        $this->assertEquals($cond->__toString(), '{language:any}');
        $this->assertEquals($cond->get_standalone_description(true, true, $info), 'Not available unless: ');

        $course = $generator->create_course(['lang' => 'FR']);
        $page = $generator->get_plugin_generator('mod_page')->create_instance(['course' => $course]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($page->cmid);
        $PAGE->set_url('/course/modedit.php', ['update' => $page->cmid]);
        \core_availability\frontend::include_all_javascript($course, $cm);

        $this->setAdminUser();
        $DB->set_field('user', 'lang', '', ['id' => $user->id]);
        $cond = new condition((object)['type' => 'language', 'id' => '']);
        $this->assertTrue($cond->is_available(false, $info, false, $user->id));
    }

    /**
     * Tests using language condition in front end.
     */
    public function test_other() {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $condition = \availability_language\condition::get_json('fr');
        $this->assertEquals($condition, (object)['type' => 'language', 'id' => 'fr']);
    }

    /**
     * Test privacy.
     */
    public function test_privacy() {
        $privacy = new availability_language\privacy\provider();
        $this->assertEquals($privacy->get_reason(), 'privacy:metadata');
    }
}