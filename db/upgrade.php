<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_hypercast_upgrade($oldversion): bool {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023011618) {

        // Define table hyper_log_entry to be created.
        $table = new xmldb_table('hyper_log_entry');

        // Adding fields to table hyper_log_entry.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('event', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table hyper_log_entry.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'hyper_groups', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for hyper_log_entry.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hypercast savepoint reached.
        upgrade_mod_savepoint(true, 2023011618, 'hypercast');
    }



    if ($oldversion < 2022111713) {

        // Define table hyper_audio_progress to be created.
        $table = new xmldb_table('hyper_audio_progress');

        // Adding fields to table hyper_audio_progress.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('hypercastid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table hyper_audio_progress.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('hypercastid', XMLDB_KEY_FOREIGN, ['hypercastid'], 'hypercast', ['id']);

        // Conditionally launch create table for hyper_audio_progress.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hypercast savepoint reached.
        upgrade_mod_savepoint(true, 2022111713, 'hypercast');
    }

    if ($oldversion < 2022111917) {

        // Define table hyper_comments to be created.
        $table = new xmldb_table('hyper_comments');

        // Adding fields to table hyper_comments.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('comment', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hypercastid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('referenceid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table hyper_comments.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'hyper_groups', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'hyper_groups_users', ['id']);
        $table->add_key('hypercastid', XMLDB_KEY_FOREIGN, ['hypercastid'], 'hypercast', ['id']);

        // Conditionally launch create table for hyper_comments.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hypercast savepoint reached.
        upgrade_mod_savepoint(true, 2022111917, 'hypercast');
    }

    if ($oldversion < 2022112410) {

        // Define field id to be added to hyper_comments.
        $table = new xmldb_table('hyper_comments');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2022112410, 'hypercast');
    }

    if ($oldversion < 2022122400) {

        // Define field hideuser to be added to hyper_groups_users.
        $table = new xmldb_table('hyper_groups_users');
        $field = new xmldb_field('hideuser', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'timemodified');

        // Conditionally launch add field hideuser.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field hideothers to be added to hyper_groups_users.
        $table = new xmldb_table('hyper_groups_users');
        $field = new xmldb_field('hideothers', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'hideuser');

        // Conditionally launch add field hideothers.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field audiocues to be added to hyper_groups_users.
        $table = new xmldb_table('hyper_groups_users');
        $field = new xmldb_field('audiocues', XMLDB_TYPE_TEXT, null, null, null, null, null, 'hideothers');

        // Conditionally launch add field audiocues.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Hypercast savepoint reached.
        upgrade_mod_savepoint(true, 2022122400, 'hypercast');
    }

    if ($oldversion < 2022122800) {

        // Define field id to be added to hyper_comments.
        $table = new xmldb_table('hyper_comments');
        $field = new xmldb_field('category', XMLDB_TYPE_TEXT);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2022122800, 'hypercast');
    }

    return true;
}
