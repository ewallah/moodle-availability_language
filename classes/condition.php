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
 * Condition main class.
 *
 * @package availability_language
 * @copyright 2014 Renaat Debleu (www.eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_language;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition main class.
 *
 * @package availability_language
 * @copyright 2014 Renaat Debleu (www.eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var string ID of language that this condition requires, or '' = any language */
    protected $languageid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get language id.
        if (!property_exists($structure, 'id')) {
            $this->languageid = '';
        } else if (is_string($structure->id)) {
            $this->languageid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for language condition');
        }
    }

    public function save() {
        $result = (object)array('type' => 'language');
        if ($this->languageid) {
            $result->id = $this->languageid;
        }
        return $result;
    }

    /**
     *
     *
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        // If course has forced language.
        $course = $info->get_course();
        $allow = false;
        
        if (isset($course->lang) && $course->lang == $this->languageid) {
            $allow = true;
        }
        if (current_language() == $this->languageid) {
            $allow = true;
        }
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        if ($this->languageid == '') {
            return '';
        }
        $installedlangs = get_string_manager()->get_list_of_translations(false);
        if ($not) {
            return get_string('getdescriptionnot', 'availability_language', $installedlangs[$this->languageid]);
        }
        return get_string('getdescription', 'availability_language', $installedlangs[$this->languageid]);
    }

    protected function get_debug_string() {
        return $this->languageid ? '' . $this->languageid : 'any';
    }
}

