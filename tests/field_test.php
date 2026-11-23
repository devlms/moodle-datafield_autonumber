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

namespace datafield_autonumber;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/data/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Tests for the entry number field.
 *
 * @package    datafield_autonumber
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \data_field_autonumber
 * @covers     \datafield_autonumber\numberer
 * @covers     \datafield_autonumber\observer
 */
final class field_test extends \advanced_testcase {
    /** @var \stdClass database activity */
    private \stdClass $data;

    /** @var \data_field_base text field "Request" */
    private \data_field_base $text;

    /** @var int[] entry number fields, given an empty value like the add form does */
    private array $numberfields = [];

    /** @var \mod_data_generator */
    private \mod_data_generator $generator;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->data = $this->getDataGenerator()->create_module('data', ['course' => $course->id]);
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_data');
        $this->text = $this->generator->create_field((object)['name' => 'Request', 'type' => 'text'], $this->data);
    }

    /**
     * Add the entry number field.
     *
     * @param string $prefix
     * @param int $digits
     * @return \data_field_autonumber
     */
    private function add_number_field(string $prefix = 'REQ-', int $digits = 4): \data_field_autonumber {
        $field = $this->generator->create_field((object)['name' => 'Number', 'type' => 'autonumber',
            'param1' => $prefix, 'param2' => $digits], $this->data);
        $this->numberfields[] = $field->field->id;
        return data_get_field_from_id($field->field->id, $this->data);
    }

    /**
     * Add an entry with some text.
     *
     * @param string $text
     * @return int record id
     */
    private function add_entry(string $text): int {
        $contents = [$this->text->field->id => $text] + array_fill_keys($this->numberfields, '');
        return $this->generator->create_entry($this->data, $contents);
    }

    /**
     * How an entry's number is shown.
     *
     * @param \data_field_autonumber $field
     * @param int $recordid
     * @return string
     */
    private function shown(\data_field_autonumber $field, int $recordid): string {
        return $field->display_browse_field($recordid, 'singletemplate');
    }

    public function test_new_entries_are_numbered_in_order(): void {
        $field = $this->add_number_field();
        $first = $this->add_entry('Projector broken');
        $second = $this->add_entry('Need a room');
        $this->assertSame('REQ-0001', $this->shown($field, $first));
        $this->assertSame('REQ-0002', $this->shown($field, $second));
    }

    public function test_existing_entries_are_numbered_when_the_field_is_added(): void {
        global $DB;
        $first = $this->add_entry('Old one');
        $second = $this->add_entry('Older? no, newer');
        $DB->set_field('data_records', 'timecreated', 100, ['id' => $second]);
        $DB->set_field('data_records', 'timecreated', 200, ['id' => $first]);

        $field = $this->add_number_field('', 0);
        $this->assertSame('1', $this->shown($field, $second));
        $this->assertSame('2', $this->shown($field, $first));
        $this->assertSame('3', $this->shown($field, $this->add_entry('After')));
    }

    public function test_numbers_are_never_reused(): void {
        $field = $this->add_number_field();
        $this->add_entry('One');
        $last = $this->add_entry('Two');
        data_delete_record($last, $this->data, $this->data->course, $field->cm->id);
        $this->assertSame('REQ-0003', $this->shown($field, $this->add_entry('Three')));
    }

    public function test_editing_an_entry_keeps_its_number(): void {
        $field = $this->add_number_field();
        $recordid = $this->add_entry('One');
        $this->assertTrue($field->update_content($recordid, '999'));
        $this->assertSame('REQ-0001', $this->shown($field, $recordid));
    }

    public function test_the_add_form_shows_the_number_and_has_no_input(): void {
        $field = $this->add_number_field();
        $recordid = $this->add_entry('One');
        $this->assertStringContainsString('REQ-0001', $field->display_add_field($recordid));
        $this->assertStringNotContainsString('<input', $field->display_add_field($recordid));
        $this->assertStringContainsString(get_string('givenonsave', 'datafield_autonumber'), $field->display_add_field(0));
    }

    public function test_search_finds_an_entry_by_its_number(): void {
        global $DB;
        $field = $this->add_number_field();
        $this->add_entry('One');
        $second = $this->add_entry('Two');
        foreach (['REQ-0002', '2', ' 0002 '] as $search) {
            [$sql, $params] = $field->generate_sql('c', $search);
            $found = $DB->get_fieldset_sql("SELECT c.recordid FROM {data_content} c WHERE $sql", $params);
            $this->assertEquals([$second], $found, $search);
        }
    }

    public function test_numbers_sort_as_numbers(): void {
        global $DB;
        $field = $this->add_number_field('', 0);
        for ($i = 1; $i <= 10; $i++) {
            $this->add_entry("Entry $i");
        }
        $sort = $field->get_sort_sql('c.content');
        $numbers = $DB->get_fieldset_sql(
            "SELECT c.content FROM {data_content} c WHERE c.fieldid = ? ORDER BY $sort",
            [$field->field->id]
        );
        $this->assertEquals(range(1, 10), array_map('intval', $numbers));
    }

    public function test_export_uses_the_shown_number(): void {
        global $DB;
        $field = $this->add_number_field('INV', 3);
        $recordid = $this->add_entry('One');
        $content = $DB->get_record('data_content', ['fieldid' => $field->field->id, 'recordid' => $recordid]);
        $this->assertTrue($field->text_export_supported());
        $this->assertSame('INV001', $field->export_text_value($content));
    }

    public function test_settings_are_checked(): void {
        $field = $this->add_number_field();
        $this->assertSame([], $field->validate((object)['param1' => 'REQ-', 'param2' => '4']));
        $this->assertArrayHasKey('param2', $field->validate((object)['param1' => '', 'param2' => '12']));
        $this->assertArrayHasKey('param2', $field->validate((object)['param1' => '', 'param2' => 'four']));
        $this->assertArrayHasKey('param1', $field->validate((object)['param1' => str_repeat('x', 21), 'param2' => '0']));
    }

    public function test_entries_added_through_the_web_service_are_numbered(): void {
        $field = $this->add_number_field();
        $student = $this->getDataGenerator()->create_and_enrol(get_course($this->data->course), 'student');
        $this->setUser($student);
        $result = \mod_data_external::add_entry($this->data->id, 0, [
            ['fieldid' => $this->text->field->id, 'value' => json_encode('From the app')],
        ]);
        $this->assertSame('REQ-0001', $this->shown($field, $result['newentryid']));
        numberer::remove_empty_duplicates((int)$field->field->id, (int)$result['newentryid']);
        $this->assertSame(1, $this->count_rows($field, $result['newentryid']));
        $this->assertSame('REQ-0001', $this->shown($field, $result['newentryid']));
    }

    public function test_the_add_entry_page_leaves_one_numbered_row(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/data/locallib.php');
        $field = $this->add_number_field();
        // What mod/data/edit.php does when a new entry is saved.
        $fields = $DB->get_records('data_fields', ['dataid' => $this->data->id]);
        $datarecord = (object)['field_' . $this->text->field->id => 'From the form'];
        $processed = data_process_submission($this->data, $fields, $datarecord);
        $recordid = data_add_record($this->data);
        data_add_fields_contents_to_new_record($this->data, $field->context, $recordid, $fields, $datarecord, $processed);

        $this->assertSame('REQ-0001', $this->shown($field, $recordid));
        $this->assertSame(2, $this->count_rows($field, $recordid), 'mod_data adds an empty row after the numbered one');
        numberer::remove_empty_duplicates((int)$field->field->id, $recordid);
        $this->assertSame(1, $this->count_rows($field, $recordid));
        $this->assertSame('REQ-0002', $this->shown($field, $this->add_entry('Next')));
    }

    public function test_csv_import_numbers_entries_and_ignores_numbers_in_the_file(): void {
        global $DB;
        $field = $this->add_number_field();
        $this->add_entry('Already here');
        $csv = make_request_directory() . '/entries.csv';
        file_put_contents($csv, "Request,Number\nImported one,REQ-0999\nImported two,\n");
        $cm = get_coursemodule_from_instance('data', $this->data->id);
        $importer = new \mod_data\local\importer\csv_entries_importer($csv, 'entries.csv');
        $importer->import_csv($cm, $this->data, 'UTF-8', 'comma');

        $numbers = $DB->get_records_menu('data_content', ['fieldid' => $field->field->id], 'recordid', 'recordid, content');
        $this->assertSame(['1', '2', '3'], array_values($numbers));
    }

    /**
     * Content rows of the number field for an entry.
     *
     * @param \data_field_autonumber $field
     * @param int $recordid
     * @return int
     */
    private function count_rows(\data_field_autonumber $field, int $recordid): int {
        global $DB;
        return $DB->count_records('data_content', ['fieldid' => $field->field->id, 'recordid' => $recordid]);
    }
}
