<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace datafield_autonumber\backup;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/phpunit/classes/restore_date_testcase.php');
require_once($CFG->dirroot . '/mod/data/lib.php');

/**
 * Backup and restore keep entry numbers and the counter.
 *
 * @package    datafield_autonumber
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \data_field_autonumber
 */
final class backup_restore_test extends \restore_date_testcase {
    public function test_numbers_and_counter_come_back_with_the_course(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $data = $this->getDataGenerator()->create_module('data', ['course' => $course->id]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_data');
        $text = $generator->create_field((object)['name' => 'Request', 'type' => 'text'], $data);
        $number = $generator->create_field((object)['name' => 'Number', 'type' => 'autonumber', 'param1' => 'REQ-',
            'param2' => 4], $data);
        foreach (['One', 'Two', 'Three'] as $request) {
            $generator->create_entry($data, [$text->field->id => $request, $number->field->id => '']);
        }
        $DB->delete_records('data_records', ['id' => $DB->get_field_sql('SELECT MAX(id) FROM {data_records}')]);

        $newcourseid = $this->backup_and_restore($course);

        $newdata = $DB->get_record('data', ['course' => $newcourseid], '*', MUST_EXIST);
        $newfield = $DB->get_record('data_fields', ['dataid' => $newdata->id, 'type' => 'autonumber'], '*', MUST_EXIST);
        $this->assertSame('3', $newfield->param3, 'The counter is kept, so number 3 is not given again');
        $restored = $DB->get_fieldset_select('data_content', 'content', 'fieldid = ? ORDER BY recordid', [$newfield->id]);
        $this->assertEquals(['1', '2'], $restored);

        $generator->create_entry($newdata, [
            $DB->get_field('data_fields', 'id', ['dataid' => $newdata->id, 'type' => 'text']) => 'Four',
            $newfield->id => '',
        ]);
        $this->assertEquals(4, $DB->get_field_sql('SELECT MAX(' . $DB->sql_cast_char2int('content') . ') FROM {data_content}
            WHERE fieldid = ?', [$newfield->id]));
    }
}
