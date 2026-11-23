# Entry number field for the Moodle Database activity (datafield_autonumber)

Gives every entry in a Database activity its own number, like `REQ-0042` or `INV017`. Useful for request registers, equipment loans, incident logs, ticket lists, anything people refer to by number.

## Use it

In the Database activity, open **Fields**, create an **Entry number** field, and choose:

- **Prefix**: text before the number, such as `REQ-` (optional, up to 20 characters).
- **Digits**: pad with zeros to this many digits, 0 to 9. With 4, number 42 shows as `0042`.

Every new entry gets the next number when it is saved, and keeps it when edited. Nobody can type or change it.

## Good to know

- Entries already in the database are numbered in the order they were added when you create the field.
- Numbers of deleted entries are not reused.
- Entries added through the Moodle app, web services or a CSV import are numbered too.
- Search finds `REQ-0042`, `0042` or `42`. Sorting by the field sorts as numbers, so 10 comes after 9. Exports include the number as shown.
- Two entries saved at the same moment never get the same number.
- Backups and restores keep the numbers and the counter.

Moodle 4.5 to 5.3. Built by DevLMS: https://devlms.com
