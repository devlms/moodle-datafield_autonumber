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

/**
 * Numbers every new entry, however it was added: the form, the Moodle app, a CSV import.
 *
 * @package    datafield_autonumber
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * An entry was added to a database.
     *
     * @param \mod_data\event\record_created $event
     */
    public static function record_created(\mod_data\event\record_created $event): void {
        global $DB;
        $fields = $DB->get_records('data_fields', ['dataid' => $event->other['dataid'], 'type' => 'autonumber']);
        foreach ($fields as $field) {
            numberer::assign($field, (int)$event->objectid);
            if (!(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
                // Runs after mod_data has added its empty rows (see numberer::remove_empty_duplicates()).
                \core_shutdown_manager::register_function(
                    [numberer::class, 'remove_empty_duplicates'],
                    [(int)$field->id, (int)$event->objectid]
                );
            }
        }
    }
}
