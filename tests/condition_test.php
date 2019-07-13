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
 * @copyright 2017 eWallah.net <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use availability_language\condition;

/**
 * Unit tests for the language condition.
 *
 * @package availability_language
 * @copyright 2017 eWallah.net <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass availability_language
 */
class availability_language_condition_testcase extends advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using language condition as part of tree.
     * @covers availability_language\condition
     */
    public function test_in_tree() {
        global $CFG;
        $this->resetAfterTest();

        // Create course with language turned on and a Page.
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_user(['lang' => 'nl']);
        $user2 = $generator->create_user();

        $info1 = new \core_availability\mock_info($course, $user1->id);
        $info2 = new \core_availability\mock_info($course, $user2->id);

        $arr1 = ['type' => 'language', 'id' => 'en'];
        $arr2 = ['type' => 'language', 'id' => 'nl'];
        $tree1 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr1]]);
        $tree2 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr2]]);

        // Initial check.
        $this->setAdminUser();
        $this->assertTrue($tree1->check_available(false, $info1, true, null)->is_available());
        $this->assertFalse($tree1->check_available(false, $info1, true, $user1->id)->is_available());
        $this->assertTrue($tree2->check_available(false, $info1, true, $user1->id)->is_available());
        $this->assertTrue($tree1->check_available(false, $info1, true, $user2->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info1, true, $user2->id)->is_available());
        $this->assertFalse($tree1->check_available(false, $info2, true, $user1->id)->is_available());
        $this->assertTrue($tree2->check_available(false, $info2, true, $user1->id)->is_available());
        $this->assertTrue($tree1->check_available(false, $info2, true, $user2->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $user2->id)->is_available());
        // Change user.
        $this->setuser($user1->id);
        $this->assertTrue($tree1->check_available(false, $info1, true, $user1->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info1, true, $user1->id)->is_available());
        $this->assertFalse($tree1->check_available(true, $info1, true, $user1->id)->is_available());
        $this->assertTrue($tree2->check_available(true, $info1, true, $user1->id)->is_available());
        $this->setuser($user2->id);
        $this->assertTrue($tree1->check_available(false, $info2, true, $user2->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $user2->id)->is_available());
        $this->assertFalse($tree1->check_available(true, $info2, true, $user2->id)->is_available());
        $this->assertTrue($tree2->check_available(true, $info2, true, $user2->id)->is_available());
    }

    /**
     * Tests section availability.
     * @covers availability_language\condition
     */
    public function test_sections() {
        global $CFG, $DB;
        $this->resetAfterTest();

        // Create course with language turned on and a Page.
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_user(['lang' => 'nl']);
        $user2 = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id);
        $generator->enrol_user($user2->id, $course->id);
        $DB->set_field('course_sections', 'availability', '{"op":"|","show":false,"c":[{"type":"language","id":"nl"}]}',
                ['course' => $course->id, 'section' => 0]);
        $DB->set_field('course_sections', 'availability', '{"op":"|","show":true,"c":[{"type":"language","id":""}]}',
                ['course' => $course->id, 'section' => 1]);
        $DB->set_field('course_sections', 'availability', '{"op":"|","show":true,"c":[{"type":"language","id":"fr"}]}',
                ['course' => $course->id, 'section' => 2]);
        $DB->set_field('course_sections', 'availability', '{"op":"|","show":true,"c":[{"type":"language","id":"en"}]}',
                ['course' => $course->id, 'section' => 3]);
        $modinfo1 = get_fast_modinfo($course, $user1->id);
        $modinfo2 = get_fast_modinfo($course, $user2->id);
        $this->assertTrue($modinfo1->get_section_info(0)->uservisible);
        $this->assertTrue($modinfo1->get_section_info(1)->uservisible);
        $this->assertFalse($modinfo1->get_section_info(2)->uservisible);
        $this->assertFalse($modinfo1->get_section_info(3)->uservisible);
        $this->assertFalse($modinfo2->get_section_info(0)->uservisible);
        $this->assertTrue($modinfo2->get_section_info(1)->uservisible);
        $this->assertFalse($modinfo2->get_section_info(2)->uservisible);
        $this->assertTrue($modinfo2->get_section_info(3)->uservisible);
    }

    /**
     * Tests the constructor including error conditions.
     * @covers availability_language\condition
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
     * @covers availability_language\condition
     */
    public function test_save() {
        $structure = (object)['id' => 'fr'];
        $cond = new condition($structure);
        $structure->type = 'language';
        $this->assertEquals($structure, $cond->save());
        $this->assertEquals((object)['type' => 'language', 'id' => ''], $cond->get_json());
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     * @covers availability_language\condition
     */
    public function test_get_description() {
        $info = new \core_availability\mock_info();
        $language = new condition((object)['type' => 'language', 'id' => '']);
        $this->assertEquals($language->get_description(false, false, $info), '');
        $language = new condition((object)['type' => 'language', 'id' => 'en']);
        $desc = $language->get_description(true, false, $info);
        $this->assertEquals('The student\'s language is English ‎(en)‎', $desc);
        $desc = $language->get_description(true, true, $info);
        $this->assertEquals('The student\'s language is not English ‎(en)‎', $desc);
        $desc = $language->get_standalone_description(true, false, $info);
        $this->assertContains('Not available unless: The student\'s language is English', $desc);

        $class = new ReflectionClass('availability_language\condition');
        $method = $class->getMethod('get_debug_string');
        $method->setAccessible(true);
        $this->assertEquals('en', $method->invokeArgs($language, []));
    }

    /**
     * Tests using language condition in front end.
     * @covers availability_language\frontend
     */
    public function test_frontend() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/lesson/locallib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $les = new lesson($generator->get_plugin_generator('mod_lesson')->create_instance(['course' => $course, 'section' => 0]));
        $user = $generator->create_user();
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($les->cmid);
        $sections = $modinfo->get_section_info_all();
        $generator->enrol_user($user->id, $course->id);

        $frontend = new availability_language\frontend();
        $class = new ReflectionClass('availability_language\frontend');
        $method = $class->getMethod('get_javascript_strings');
        $method->setAccessible(true);
        $this->assertEquals([], $method->invokeArgs($frontend, []));
        $method = $class->getMethod('get_javascript_init_params');
        $method->setAccessible(true);
        $this->assertEquals(1, count($method->invokeArgs($frontend, [$course])));
        $method = $class->getMethod('allow_add');
        $method->setAccessible(true);
        $this->assertFalse($method->invokeArgs($frontend, [$course]));
        $this->assertFalse($method->invokeArgs($frontend, [$course, $cm, null]));
        $this->assertFalse($method->invokeArgs($frontend, [$course, null, $sections[0]]));
        $this->assertFalse($method->invokeArgs($frontend, [$course, null, $sections[1]]));
        $coursenl = $generator->create_course(['lang' => 'nl']);
        $this->assertFalse($method->invokeArgs($frontend, [$coursenl]));

        $page = $generator->get_plugin_generator('mod_page')->create_instance(['course' => $course]);
        $context = context_module::instance($page->cmid);
        $mpage = new moodle_page();
        $mpage->set_url('/course/modedit.php', ['update' => $page->cmid]);
        $mpage->set_context($context);
        $renderer = $mpage->get_renderer('core');
        $this->setuser($user);
        $mpage = new moodle_page();
        $mpage->set_url('/course/index.php', ['id' => $course->id]);
        $context = context_course::instance($course->id);
        $mpage->set_context($context);
        $renderer = $mpage->get_renderer('core');
    }

    /**
     * Test privacy.
     * @covers availability_language\privacy\provider
     */
    public function test_privacy() {
        $privacy = new availability_language\privacy\provider();
        $this->assertEquals($privacy->get_reason(), 'privacy:metadata');
    }
}