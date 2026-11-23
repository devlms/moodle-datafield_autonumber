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
 * Strings for the entry number field.
 *
 * @package    datafield_autonumber
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['digits'] = 'Digits';
$string['digits_help'] = 'Pad numbers with zeros to this many digits: 4 shows 42 as 0042. 0 for no padding.';
$string['fieldtypelabel'] = 'Entry number';
$string['givenonsave'] = 'Given when the entry is saved';
$string['invaliddigits'] = 'Enter a whole number from 0 to {$a}.';
$string['numbering'] = 'Every entry gets the next number when it is saved, and keeps it. Numbers of deleted entries are not reused. Entries already in the database are numbered in the order they were added.';
$string['pluginname'] = 'Entry number';
$string['prefix'] = 'Prefix';
$string['prefix_help'] = 'Text before the number, for example REQ- or INV. Leave empty for plain numbers.';
$string['prefixtoolong'] = 'Use at most {$a} characters.';
$string['privacy:metadata'] = 'The Entry number field stores no personal data of its own; the number is kept with the entry by the Database activity.';
