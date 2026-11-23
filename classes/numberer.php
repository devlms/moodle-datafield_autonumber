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

use stdClass;

/**
 * Gives entries their numbers. The last number given is kept in the field's param3, so numbers are never reused.
 *
 * @package    datafield_autonumber
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class numberer {
    /** @var int longest prefix */
    public const MAX_PREFIX = 20;

    /** @var int most digits to pad to */
    public const MAX_DIGITS = 9;

    /**
     * The next number for a field. Two entries saved at the same moment never get the same one.
     *
     * @param int $fieldid
     * @return int
     */
    private static function next(int $fieldid): int {
        global $DB;
        $factory = \core\lock\lock_config::get_lock_factory('datafield_autonumber');
        $lock = $factory->get_lock('field' . $fieldid, 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        try {
            $next = (int)$DB->get_field('data_fields', 'param3', ['id' => $fieldid]) + 1;
            $DB->set_field('data_fields', 'param3', (string)$next, ['id' => $fieldid]);
        } finally {
            $lock->release();
        }
        return $next;
    }

    /**
     * Give an entry its number, unless it has one. Fills the empty row mod_data may have made for the field.
     *
     * @param stdClass $field data_fields record
     * @param int $recordid
     */
    public static function assign(stdClass $field, int $recordid): void {
        global $DB;
        $rows = $DB->get_records('data_content', ['fieldid' => $field->id, 'recordid' => $recordid], 'id');
        foreach ($rows as $row) {
            if ((string)$row->content !== '') {
                return;
            }
        }
        $number = (string)self::next((int)$field->id);
        if ($rows) {
            $DB->set_field('data_content', 'content', $number, ['id' => reset($rows)->id]);
        } else {
            $DB->insert_record('data_content', (object)['fieldid' => $field->id, 'recordid' => $recordid, 'content' => $number]);
        }
    }

    /**
     * Remove the empty rows left next to the numbered one.
     *
     * The add entry page and the add_entry web service call data_add_record() (where the observer numbers the entry)
     * and then insert an empty data_content row for every field. Two rows for one field would repeat the entry when
     * the list is sorted or searched by it, so the empty ones go once the request is done.
     *
     * @param int $fieldid
     * @param int $recordid
     */
    public static function remove_empty_duplicates(int $fieldid, int $recordid): void {
        global $DB;
        $rows = $DB->get_records('data_content', ['fieldid' => $fieldid, 'recordid' => $recordid], 'id');
        $numbered = array_filter($rows, fn($row) => (string)$row->content !== '');
        if (!$numbered) {
            return;
        }
        foreach ($rows as $row) {
            if ((string)$row->content === '' || $row->id != reset($numbered)->id) {
                $DB->delete_records('data_content', ['id' => $row->id]);
            }
        }
    }

    /**
     * Number the entries that have no number yet, oldest first. Used when the field is added to a database.
     *
     * @param stdClass $field data_fields record
     */
    public static function number_existing(stdClass $field): void {
        global $DB;
        $recordids = $DB->get_fieldset_sql(
            'SELECT r.id
               FROM {data_records} r
          LEFT JOIN {data_content} c ON c.recordid = r.id AND c.fieldid = :fieldid
              WHERE r.dataid = :dataid AND c.id IS NULL
           ORDER BY r.timecreated, r.id',
            ['fieldid' => $field->id, 'dataid' => $field->dataid]
        );
        foreach ($recordids as $recordid) {
            self::assign($field, (int)$recordid);
        }
    }

    /**
     * The number as shown: prefix and zero padding, for example REQ-0042.
     *
     * @param stdClass $field data_fields record
     * @param string|int $number
     * @return string plain text
     */
    public static function format(stdClass $field, $number): string {
        $digits = max(0, min(self::MAX_DIGITS, (int)$field->param2));
        return (string)$field->param1 . str_pad((string)(int)$number, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * The number someone searched for: "REQ-0042", "0042" and "42" all mean 42.
     *
     * @param string $search
     * @return int|null
     */
    public static function parse(string $search): ?int {
        if (!preg_match('/(\d+)\s*$/', trim($search), $matches)) {
            return null;
        }
        return (int)$matches[1];
    }
}
