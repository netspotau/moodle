<?php  // $Id$
/**
* Sets up the tabs used by the lesson pages for teachers.
*
* This file was adapted from the mod/quiz/tabs.php
*
* @version $Id$
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package lesson
*/

/// This file to be included so we can assume config.php has already been included.

    if (empty($id)) {
        print_error('You cannot call this script in that way');
    }
    if (!isset($currenttab)) {
        $currenttab = '';
    }
    if (!isset($cm)) {
        $cm = get_coursemodule_from_instance('groupselect', $id);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    }
    if (!isset($course)) {
        $course = get_record('course', 'id', $lesson->course);
    }

    $tabs = $row = $inactive = $activated = array();

    $row[] = new tabobject('view', "$CFG->wwwroot/mod/groupselect/view.php?id=$cm->id", get_string('view', 'groupselect'), get_string('view', 'groupselect'));
    $row[] = new tabobject('limits', "$CFG->wwwroot/mod/groupselect/limits.php?id=$cm->id", get_string('limits', 'groupselect'), get_string('limits', 'groupselect'));

    $tabs[] = $row;

    print_tabs($tabs, $currenttab);

?>
