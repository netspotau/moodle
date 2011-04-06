<?php  //$Id: upgrade.php,v 1.1.2.3 2009/03/06 15:47:04 mudrd8mz Exp $

// This file keeps track of upgrades to 
// the groupselect module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_groupselect_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2009020600) {

    /// Define field signuptype to be added to groupselect
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('signuptype');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'intro');
        $result = $result && $dbman->add_field($table, $field);

    /// Define field timecreated to be added to groupselect
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('timecreated');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timedue');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2009030500) {

    /// Define field targetgrouping to be added to groupselect
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('targetgrouping');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'intro');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2009061200) {
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('individual_limits');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $result = $result && $dbman->add_field($table, $field);

        $table = new xmldb_table('groupselect_limits');
        if ($result && !$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('groupselect', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('lim', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('groupselect', XMLDB_INDEX_NOTUNIQUE, array('groupselect'));

            $result = $result && $dbman->create_table($table);
        }
    }
    
    // Define field introformat to be added to groupselect
    if ($result && $oldversion < 2010101300) {
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('introformat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $result = $result && $dbman->add_field($table, $field);
    }

    return $result;
}

?>
