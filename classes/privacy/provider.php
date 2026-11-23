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

namespace datafield_autonumber\privacy;

use core_privacy\local\request\writer;
use mod_data\privacy\datafield_provider;

/**
 * The number is stored by mod_data with the entry; this field keeps no personal data of its own.
 *
 * @package    datafield_autonumber
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider, datafield_provider {
    /**
     * Why there is no data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }

    /**
     * Export the entry number as shown.
     *
     * @param \context $context
     * @param \stdClass $recordobj
     * @param \stdClass $fieldobj
     * @param \stdClass $contentobj
     * @param \stdClass $defaultvalue
     */
    public static function export_data_content($context, $recordobj, $fieldobj, $contentobj, $defaultvalue) {
        $defaultvalue->content = \datafield_autonumber\numberer::format($fieldobj, $contentobj->content);
        writer::with_context($context)->export_data([$recordobj->id, $contentobj->id], $defaultvalue);
    }

    /**
     * Nothing to delete beyond what mod_data deletes.
     *
     * @param \context $context
     * @param \stdClass $recordobj
     * @param \stdClass $fieldobj
     * @param \stdClass $contentobj
     */
    public static function delete_data_content($context, $recordobj, $fieldobj, $contentobj) {
    }
}
