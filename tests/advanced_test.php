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

use core_availability\{mock_info, tree};
use availability_language\condition;
use moodle_exception;

/**
 * Unit tests for the language condition.
 *
 * @package   availability_language
 * @copyright 2022 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class advanced_test extends \advanced_testcase {

    /** @var stdClass course. */
    private $course;

    /** @var stdClass usernl. */
    private $usernl;

    /** @var stdClass useren. */
    private $useren;

    /**
     * Load required classes.
     */
    public function setUp():void {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
        set_config('enableavailability', true);
        // MDL-68333 hack when language is not installed.
        mkdir("$CFG->dataroot/lang/de", 0777, true);
        mkdir("$CFG->dataroot/lang/nl", 0777, true);
        mkdir("$CFG->dataroot/lang/fr", 0777, true);
        $this->setAdminUser();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->usernl = $generator->create_user(['lang' => 'nl']);
        $generator->enrol_user($this->usernl->id, $this->course->id);
        $this->useren = $generator->create_user(['lang' => 'en']);
        $generator->enrol_user($this->useren->id, $this->course->id);
    }

    /**
     * Tests constructing and using language condition as part of tree.
     * @covers \availability_language\condition
     */
    public function test_in_tree() {
        $info1 = new mock_info($this->course, $this->usernl);
        $info2 = new mock_info($this->course, $this->useren);

        $arr1 = ['type' => 'language', 'id' => 'en'];
        $arr2 = ['type' => 'language', 'id' => 'nl'];
        $arr3 = ['type' => 'language', 'id' => ''];

        $tree1 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr1]]);
        $tree2 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr2]]);
        $tree3 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr3]]);

        // Initial check.
        $this->assertTrue($tree1->check_available(false, $info1, true, null)->is_available());
        $this->assertFalse($tree1->check_available(false, $info1, true, $this->usernl->id)->is_available());
        $this->assertTrue($tree2->check_available(false, $info1, true, $this->usernl->id)->is_available());
        $this->assertTrue($tree1->check_available(false, $info1, true, $this->useren->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info1, true, $this->useren->id)->is_available());
        $this->assertFalse($tree1->check_available(false, $info2, true, $this->usernl->id)->is_available());
        $this->assertTrue($tree2->check_available(false, $info2, true, $this->usernl->id)->is_available());
        $this->assertTrue($tree1->check_available(false, $info2, true, $this->useren->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $this->useren->id)->is_available());
        $this->assertTrue($tree1->check_available(false, $info2, true, $this->useren->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $this->useren->id)->is_available());
        $this->assertTrue($tree3->check_available(false, $info1, true, $this->useren->id)->is_available());
        $this->assertFalse($tree3->check_available(true, $info1, true, $this->useren->id)->is_available());
        $this->assertTrue($tree3->check_available(false, $info1, false, $this->useren->id)->is_available());

        // Change user.
        $this->setuser($this->usernl);
        $this->assertFalse($tree1->check_available(false, $info1, true, $this->usernl->id)->is_available());
        $this->assertTrue($tree2->check_available(false, $info1, true, $this->usernl->id)->is_available());
        $this->assertFalse($tree1->check_available(false, $info1, false, $this->usernl->id)->is_available());
        $this->assertTrue($tree2->check_available(false, $info1, false, $this->usernl->id)->is_available());
        $this->assertTrue($tree1->check_available(true, $info1, true, $this->usernl->id)->is_available());
        $this->assertFalse($tree2->check_available(true, $info1, true, $this->usernl->id)->is_available());
        $this->assertTrue($tree1->check_available(true, $info1, false, $this->usernl->id)->is_available());
        $this->assertFalse($tree2->check_available(true, $info1, false, $this->usernl->id)->is_available());
        $this->assertTrue($tree1->is_available_for_all(true));
        $this->assertFalse($tree1->is_available_for_all(false));
        $this->assertFalse($tree2->is_available_for_all(true));
        $this->assertTrue($tree2->is_available_for_all(false));
        $this->assertFalse($tree3->is_available_for_all(true));
        $this->assertTrue($tree3->is_available_for_all(false));

        $this->setuser($this->useren);
        $this->assertTrue($tree1->check_available(false, $info2, true, $this->useren->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $this->useren->id)->is_available());
        $this->assertTrue($tree1->check_available(false, $info2, false, $this->useren->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, false, $this->useren->id)->is_available());
        $this->assertFalse($tree1->check_available(true, $info2, true, $this->useren->id)->is_available());
        $this->assertTrue($tree2->check_available(true, $info2, true, $this->useren->id)->is_available());
        $this->assertFalse($tree1->check_available(true, $info2, false, $this->useren->id)->is_available());
        $this->assertTrue($tree2->check_available(true, $info2, false, $this->useren->id)->is_available());
        $this->assertFalse($tree1->is_available_for_all(true));
        $this->assertTrue($tree1->is_available_for_all(false));
        $this->assertTrue($tree2->is_available_for_all(true));
        $this->assertFalse($tree2->is_available_for_all(false));
    }

    /**
     * Tests section availability.
     * @covers \availability_language\condition
     */
    public function test_sections() {
        global $DB;
        $cond = '{"op":"|","show":false,"c":[{"type":"language","id":"nl"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $this->course->id, 'section' => 0]);
        $cond = '{"op":"|","show":true,"c":[{"type":"language","id":""}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $this->course->id, 'section' => 1]);
        $cond = '{"op":"|","show":true,"c":[{"type":"language","id":"fr"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $this->course->id, 'section' => 2]);
        $cond = '{"op":"|","show":true,"c":[{"type":"language","id":"en"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $this->course->id, 'section' => 3]);
        $modinfo1 = get_fast_modinfo($this->course, $this->usernl->id);
        $modinfo2 = get_fast_modinfo($this->course, $this->useren->id);
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
     * Tests using language condition in front end.
     * @covers \availability_language\frontend
     */
    public function test_frontend() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/lesson/locallib.php');
        $params = ['course' => $this->course, 'section' => 0];
        $generator = $this->getDataGenerator();
        $les = new \lesson($generator->get_plugin_generator('mod_lesson')->create_instance($params));
        $params['lang'] = 'nl';
        $page = $generator->get_plugin_generator('mod_page')->create_instance($params);
        $modinfo = get_fast_modinfo($this->course);
        $cm1 = $modinfo->get_cm($les->cmid);
        $cm2 = $modinfo->get_cm($page->cmid);
        $sections = $modinfo->get_section_info_all();

        $name = 'availability_language\frontend';
        $front = new \availability_language\frontend();
        $this->assertCount(4, get_string_manager()->get_list_of_translations(true));
        $this->assertTrue(\phpunit_util::call_internal_method($front, 'allow_add', [$this->course, $cm1, $sections[1]], $name));
        $this->assertTrue(\phpunit_util::call_internal_method($front, 'allow_add', [$this->course, $cm2, $sections[1]], $name));
        $this->assertCount(1, \phpunit_util::call_internal_method($front, 'get_javascript_init_params', [$this->course], $name));
        $course = $generator->create_course(['lang' => 'nl']);
        $this->assertFalse(\phpunit_util::call_internal_method($front, 'allow_add', [$course, $cm1, $sections[1]], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($front, 'allow_add', [$course, $cm2, $sections[1]], $name));
        $this->assertCount(1, \phpunit_util::call_internal_method($front, 'get_javascript_init_params', [$course], $name));
    }


    /**
     * Tests using language condition in back end.
     * @covers \availability_language\condition
     */
    public function test_backend() {
        global $CFG, $PAGE;
        $generator = $this->getDataGenerator();
        $context = \context_course::instance($this->course->id);
        $pagegen = $generator->get_plugin_generator('mod_page');
        $restriction = \core_availability\tree::get_root_json([condition::get_json('fr')]);
        $pagegen->create_instance(['course' => $this->course, 'availability' => json_encode($restriction)]);
        $restriction = \core_availability\tree::get_root_json([condition::get_json('en')]);
        $pagegen->create_instance(['course' => $this->course, 'availability' => json_encode($restriction)]);
        $restriction = \core_availability\tree::get_root_json([condition::get_json('nl')]);
        $pagegen->create_instance(['course' => $this->course, 'availability' => json_encode($restriction)]);
        rebuild_course_cache($this->course->id, true);
        $mpage = new \moodle_page();
        $mpage->set_url('/course/index.php', ['id' => $this->course->id]);
        $PAGE->set_url('/course/index.php', ['id' => $this->course->id]);
        $mpage->set_context($context);
        $format = course_get_format($this->course);
        $renderer = $mpage->get_renderer('format_topics');
        $branch = (int)$CFG->branch;
        if ($branch > 311) {
            $outputclass = $format->get_output_classname('content');
            $output = new $outputclass($format);
            ob_start();
            echo $renderer->render($output);
        } else {
            ob_start();
            echo $renderer->print_multiple_section_page($this->course, null, null, null, null);
        }
        $out = ob_get_clean();
        $this->assertStringContainsString('Not available unless: The student\'s language is English ‎(en)', $out);
        $this->setuser($this->useren);
        ob_start();
        if ($branch > 311) {
            echo $renderer->render($output);
        } else {
            echo $renderer->print_multiple_section_page($this->course, null, null, null, null);
        }
        $out = ob_get_clean();
        $this->assertStringNotContainsString('Not available unless: The student\'s language is English ‎(en)', $out);
    }
}
