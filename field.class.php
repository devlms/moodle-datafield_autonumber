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

/**
 * Entry number field: every entry gets the next number, like REQ-0042.
 *
 * @package    datafield_autonumber
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use datafield_autonumber\numberer;

/**
 * The entry number field.
 */
class data_field_autonumber extends data_field_base {
    /** @var string field type */
    public $type = 'autonumber';

    /**
     * The field can be previewed in templates.
     *
     * @return bool
     */
    public function supports_preview(): bool {
        return true;
    }

    /**
     * Sample content for template previews.
     *
     * @param int $recordid
     * @return stdClass
     */
    public function get_data_content_preview(int $recordid): stdClass {
        return (object)[
            'id' => 0, 'fieldid' => $this->field->id, 'recordid' => $recordid, 'content' => (string)(41 + $recordid),
            'content1' => null, 'content2' => null, 'content3' => null, 'content4' => null,
        ];
    }

    /**
     * Add the field, starting the count at 0, then number the entries already in the database.
     *
     * @return bool
     */
    public function insert_field() {
        $this->field->param3 = '0';
        parent::insert_field();
        numberer::number_existing($this->field);
        return true;
    }

    /**
     * On the add and edit form the number is shown, never edited.
     *
     * @param int $recordid
     * @param stdClass|null $formdata
     * @return string
     */
    public function display_add_field($recordid = 0, $formdata = null) {
        $shown = $recordid ? $this->display_browse_field($recordid, '') : '';
        if ($shown === '') {
            $shown = s(get_string('givenonsave', 'datafield_autonumber'));
        }
        return html_writer::div(
            html_writer::span($shown, 'datafield_autonumber form-control-plaintext'),
            '',
            ['title' => $this->field->description]
        );
    }

    /**
     * The numbered content row, even while an empty duplicate is still there.
     *
     * @param int $recordid
     * @return stdClass|false
     */
    protected function get_data_content(int $recordid) {
        global $DB;
        if ($this->preview) {
            return $this->get_data_content_preview($recordid);
        }
        $rows = $DB->get_records('data_content', ['fieldid' => $this->field->id, 'recordid' => $recordid], 'id');
        foreach ($rows as $row) {
            if ((string)$row->content !== '') {
                return $row;
            }
        }
        return reset($rows);
    }

    /**
     * The number with its prefix and padding.
     *
     * @param int $recordid
     * @param string $template
     * @return string
     */
    public function display_browse_field($recordid, $template) {
        $content = $this->get_data_content($recordid);
        if (!$content || !isset($content->content) || $content->content === '') {
            return '';
        }
        return s(numberer::format($this->field, $content->content));
    }

    /**
     * Numbers are given by the observer, never taken from a form, an import or the app.
     *
     * @param int $recordid
     * @param mixed $value
     * @param string $name
     * @return bool
     */
    public function update_content($recordid, $value, $name = '') {
        return true;
    }

    /**
     * CSV imports leave the number alone: the entry was numbered when the import created it.
     *
     * Without this method the importer inserts the file's value as a second content row.
     *
     * @param int $recordid
     * @param mixed $value
     * @param string $name
     * @return bool
     */
    public function update_content_import($recordid, $value, $name = '') {
        return true;
    }

    /**
     * The field never counts as filled in by the user.
     *
     * @param mixed $value
     * @param string $name
     * @return bool
     */
    public function notemptyfield($value, $name) {
        return false;
    }

    /**
     * Search box.
     *
     * @param string $value
     * @return string
     */
    public function display_search_field($value = '') {
        return html_writer::label(s($this->field->name), 'f_' . $this->field->id, false, ['class' => 'accesshide']) .
            html_writer::empty_tag('input', ['type' => 'text', 'size' => 12, 'id' => 'f_' . $this->field->id,
                'name' => 'f_' . $this->field->id, 'value' => $value, 'class' => 'form-control d-inline']);
    }

    /**
     * Read the search box.
     *
     * @param array|null $defaults
     * @return string
     */
    public function parse_search_field($defaults = null) {
        $param = 'f_' . $this->field->id;
        return optional_param($param, $defaults[$param] ?? '', PARAM_NOTAGS);
    }

    /**
     * Find the entry with that number. "REQ-0042", "0042" and "42" all find number 42.
     *
     * @param string $tablealias
     * @param string $value
     * @return array sql and params
     */
    public function generate_sql($tablealias, $value) {
        global $DB;
        static $i = 0;
        $i++;
        $number = numberer::parse((string)$value);
        if ($number === null) {
            return [" ({$tablealias}.fieldid = {$this->field->id} AND 1 = 0) ", []];
        }
        $name = "df_autonumber_$i";
        $content = $DB->sql_compare_text("{$tablealias}.content");
        return [" ({$tablealias}.fieldid = {$this->field->id} AND $content = :$name) ", [$name => (string)$number]];
    }

    /**
     * Sort as numbers, so 10 comes after 9.
     *
     * @param string $fieldname
     * @return string
     */
    public function get_sort_sql($fieldname) {
        global $DB;
        return $DB->sql_cast_char2int($fieldname, true);
    }

    /**
     * Exports include the number as shown.
     *
     * @return bool
     */
    public function text_export_supported() {
        return true;
    }

    /**
     * The number as shown, for exports.
     *
     * @param stdClass $record data_content record
     * @return string
     */
    public function export_text_value(stdClass $record) {
        return numberer::format($this->field, $record->content);
    }

    /**
     * Check the prefix and the number of digits.
     *
     * @param stdClass $fieldinput
     * @return array errors by field name
     */
    public function validate(stdClass $fieldinput): array {
        $errors = [];
        if (\core_text::strlen(trim($fieldinput->param1 ?? '')) > numberer::MAX_PREFIX) {
            $errors['param1'] = get_string('prefixtoolong', 'datafield_autonumber', numberer::MAX_PREFIX);
        }
        $digits = trim((string)($fieldinput->param2 ?? '0'));
        if (!preg_match('/^\d$/', $digits)) {
            $errors['param2'] = get_string('invaliddigits', 'datafield_autonumber', numberer::MAX_DIGITS);
        }
        return $errors;
    }

    /**
     * Settings for web service clients.
     *
     * @return array
     */
    public function get_config_for_external() {
        return ['param1' => $this->field->param1, 'param2' => $this->field->param2];
    }
}
