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
 * Front-end class.
 *
 * @package availability_language
 * @copyright 2018 Renaat Debleu (www.eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_language;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 *
 * @package availability_language
 * @copyright 2018 Renaat Debleu (www.eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Gets a list of string identifiers that are required in JavaScript for this plugin.
     *
     * @return array Array of required string identifiers
     */
    protected function get_javascript_strings() {
        return [];
    }

    /**
     * Gets additional parameters for the plugin's initInner function.
     *
     * Returns an array of array of id, name
     *
     * @param stdClass $course Course object
     * @param cm_info $cm Course-module currently being edited (null if none)
     * @param section_info $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        $langs = get_string_manager()->get_list_of_translations(false);
        $jsarray = [];
        foreach ($langs as $key => $value) {
            $jsarray[] = (object)['id' => $key, 'name' => $value];
        }
        return [$jsarray];
    }

    /**
     * Language condition should be available if
     *     the course language is not forced, or
     *     more than language is installed.
     *
     * @param stdClass $course Course object
     * @param cm_info $cm Course-module currently being edited (null if none)
     * @param section_info $section Section currently being edited (null if none)
     * @return bool True if available
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        // If forced course language.
        if ($course->lang != '') {
            return false;
        }
        // If there is only one language installed.
        $installedlangs = get_string_manager()->get_list_of_translations(false);
        return count($installedlangs) > 1;
    }
}
