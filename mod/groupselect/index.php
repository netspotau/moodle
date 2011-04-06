<?php // $Id: index.php,v 1.1.2.2 2008/08/06 10:14:54 thepurpleblob Exp $
    require_once('../../config.php');
    require_once('lib.php');

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = $DB->get_record('course', array('id'=>$id))) {
        print_error('Course ID is incorrect');
    }
    $params = array();
    $params['id'] = $id;

    require_course_login($course);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

    add_to_log($course->id, 'groupselect', 'view all', "index.php?id=$course->id", '');


/// Get all required strings

    $strgroupselects = get_string('modulenameplural', 'groupselect');
    $strgroupselect  = get_string('modulename', 'groupselect');


/// Print the header

    $PAGE->set_url('/mod/groupselect/index.php', $params);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_title("$course->shortname: $strgroupselects");
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_cacheable(true);
    $PAGE->navbar->add($strgroupselects);
    echo $OUTPUT->header();

/// Get all the appropriate data

    if (! $groupselects = get_all_instances_in_course('groupselect', $course)) {
        notice(get_string('thereareno', 'moodle', $strgroupselects), "../../course/view.php?id=$course->id");
        die();
    }

/// Print the list of instances (your module will probably extend this)

    $timenow  = time();
    $strname  = get_string('name');
    $strweek  = get_string('week');
    $strtopic = get_string('topic');

    $table = new html_table();
    if ($course->format == 'weeks') {
        $table->head  = array ($strweek, $strname);
        $table->align = array ('center', 'left');
    } else if ($course->format == 'topics') {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ('center', 'left', 'left', 'left');
    } else {
        $table->head  = array ($strname);
        $table->align = array ('left', 'left', 'left');
    }

    $currentsection = '';
    foreach ($groupselects as $groupselect) {
        if (!$groupselect->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$groupselect->coursemodule\">".format_string($groupselect->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$groupselect->coursemodule\">".format_string($groupselect->name,true)."</a>";
        }
        $printsection = '';
        if ($groupselect->section !== $currentsection) {
            if ($groupselect->section) {
                $printsection = $groupselect->section;
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $groupselect->section;
        }
        if ($course->format == 'weeks' or $course->format == 'topics') {
            $table->data[] = array ($printsection, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo '<br />';
    echo html_writer::table($table);

/// Finish the page

    echo $OUTPUT->footer();
?>
