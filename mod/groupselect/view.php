<?php  // $Id: view.php,v 1.2.2.7 2009/04/17 16:59:57 anic Exp $

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once('lib.php');

    $id      = required_param('id', PARAM_INT);    // Course Module ID, or
    $signup  = optional_param('signup', 0, PARAM_INT);
    $signout  = optional_param('signout', 0, PARAM_INT);
    $confirm = optional_param('confirm', 0, PARAM_BOOL);

    $params = array();
    $params['id'] = $id;
    if ($signup)
        $params['signup'] = $signup;
    if ($signout)
        $params['signout'] = $signout;
    if ($confirm)
        $params['confirm'] = $confirm;
    $PAGE->set_url('/mod/groupselect/view.php', $params);

    if ($id) {
        if (!$cm = get_coursemodule_from_id('groupselect', $id)) {
            print_error("Course Module ID was incorrect");
        }
        if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error("Course is misconfigured");
        }
        if (!$groupselect = $DB->get_record('groupselect', array('id' => $cm->instance))) {
            print_error("Course module is incorrect");
        }
    } else {
        print_error('missingparameter');
    }

    require_login($course, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $groups         = groups_get_all_groups($course->id, 0, $groupselect->targetgrouping);
    $accessall      = has_capability('moodle/site:accessallgroups', $context);
    $viewfullnames  = has_capability('moodle/site:viewfullnames', $context);
    $manage         = has_capability('moodle/course:managegroups', $context);
    $havinggroups   = groups_get_all_groups($course->id, $USER->id, $groupselect->targetgrouping, 'g.id');
    $hasgroup       = !empty($havinggroups);
    $isopen         = groupselect_is_open($groupselect);
    $groupmode      = groups_get_activity_groupmode($cm, $course);
    $counts         = groupselect_group_member_counts($cm, $groupselect->targetgrouping); 
//    $mygroups       = groups_get_user_groups($course->id, $USER->id);
//    $mygroups       = isset($mygroups[$groupselect->targetgrouping]) ? $mygroups[$groupselect->targetgrouping] : array();

    if ($course->id == SITEID) {
        $viewothers = has_capability('moodle/site:viewparticipants', $sitecontext);
    } else {
        $viewothers = has_capability('moodle/course:viewparticipants', $context);
    }

    $strgroup        = get_string('group');
    $strgroupdesc    = get_string('groupdescription', 'group');
    $strgroupselect  = get_string('modulename', 'groupselect');
    $strmembers      = get_string('memberslist', 'groupselect');
    $strsignup       = get_string('signup', 'groupselect');
    $strsignout      = get_string('signout', 'groupselect');
    $straction       = get_string('action', 'groupselect');
    $strcount        = get_string('membercount', 'groupselect');

    $PAGE->set_title(format_string($groupselect->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_cm($cm);
    $PAGE->set_context($context);
    $PAGE->set_cacheable(true);

    if ($signup and !$hasgroup) {
        require_once('signup_form.php');

        $mform = new signup_form(null, $groupselect);
        $data = array('id'=>$id, 'signup'=>$signup);
        $mform->set_data($data);

        if ($mform->is_cancelled()) {
            //nothing

        } else if ($mform->get_data(false)) {
            require_once("$CFG->dirroot/group/lib.php");
            if (!isset($groups[$signup])) {
                print_error("Incorrect group id!");
            }
            $usercount = isset($counts[$signup]) ?  $counts[$signup]->usercount : 0;
            $limits = groupselect_get_limits($groupselect->id);
            $signuplimit = isset($limits[$signup]) ? $limits[$signup] : $groupselect->maxmembers;
            if ($signuplimit <= $usercount) {
                print_error("That group has already reached its signup limit");
            }
            groups_add_member($signup, $USER->id);
            add_to_log($course->id, 'groupselect', 'signup', "view.php?id=$cm->id", groups_get_group_name($signup), $cm->id, $USER->id);
            redirect("$CFG->wwwroot/mod/groupselect/view.php?id=$cm->id");
            
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->box(get_string('signupconfirm', 'groupselect', format_string($groups[$signup]->name)));
            $mform->display();
            echo $OUTPUT->footer();
            die;
        }
    }

    if ($signout and $hasgroup) {

        require_once('signout_form.php');

        $mform = new signout_form(null, $groupselect);

        $data = array('id'=>$id, 'signout'=>$signout);

        $mform->set_data($data);

        if ($mform->is_cancelled()) {
            //nothing

        } else if ($mform->get_data(false)) {
            require_once("$CFG->dirroot/group/lib.php");
            if (!isset($groups[$signout])) {
                print_error("Incorrect group id!");
            }
            groups_remove_member($signout, $USER->id);
            add_to_log($course->id, 'groupselect', 'signout', "view.php?id=$cm->id", groups_get_group_name($signout), $cm->id, $USER->id);
            redirect("$CFG->wwwroot/mod/groupselect/view.php?id=$cm->id");
            
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->box(get_string('signoutconfirm', 'groupselect', format_string($groups[$signout]->name)));
            $mform->display();
            echo $OUTPUT->footer();
            die;
        }
    }

    echo $OUTPUT->header();

    if ($manage) {
        echo '<div class="managelink"><a href="'."$CFG->wwwroot/group/index.php?id=$course->id".'">'.get_string('managegroups', 'groupselect').'</a></div>';
        $currenttab = 'view';
        include($CFG->dirroot . '/mod/groupselect/tabs.php');
    }

    if (empty($CFG->enablegroupings) or empty($cm->groupingid)) {
        echo $OUTPUT->heading(get_string('headingsimple', 'groupselect'));
    } else {
        $grouping = groups_get_grouping($cm->groupingid);        
        echo $OUTPUT->heading(get_string('headinggrouping', 'groupselect', format_string($grouping->name)));
    }

    if (!$accessall and $groupselect->timeavailable > time()) {
        notice(get_string('notavailableyet', 'groupselect', userdate($groupselect->timeavailable)), "$CFG->wwwroot/course/view.php?id=$course->id");
        die; // not reached
    }

    echo $OUTPUT->box(format_module_intro('groupselect', $groupselect, $cm->id), 'generalbox', 'intro');

    if (!$accessall and $groupselect->timedue != 0 and  $groupselect->timedue < time() and !$hasgroup) {
        echo $OUTPUT->notification(get_string('notavailableanymore', 'groupselect', userdate($groupselect->timedue)));
    }

    if ($groups) {
        $data = array();
        $limits = groupselect_get_limits($groupselect->id);

        foreach ($groups as $group) {
            $ismember  = isset($havinggroups[$group->id]);
            $usercount = isset($counts[$group->id]) ? $counts[$group->id]->usercount : 0;
            $grpname   = format_string($group->name);
            $maxmembers = isset($limits[$group->id]) ? $limits[$group->id] : $groupselect->maxmembers;

            $line = array();
            if ($ismember) {
                $grpname = '<div class="mygroup">'.$grpname.'</div>';
            }
            $line[0] = format_text($grpname);
            $line[1] = format_text($group->description);

            if ($maxmembers) {
                $line[2] = format_text($usercount.'/'.$maxmembers);
            } else {
                $line[2] = format_text($usercount);
            }

            if ($accessall) {
                $canseemembers = true;
            } else {
                if ($groupmode == SEPARATEGROUPS and !$ismember) {
                    $canseemembers = false;
                } else {
                    $canseemembers = $viewothers;
                }
            }

            if ($canseemembers) {
                if ($members = groups_get_members($group->id)) {
                    $membernames = array();
                    foreach ($members as $member) {
                        if ($member->id == $USER->id) {
                            $membernames[] = '<span class="me">'.fullname($member, $viewfullnames).'</span>';
                        } else {
                            $membernames[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$member->id.'&amp;course='.$course->id.'">' . fullname($member, $viewfullnames) . '</a>';
                        }
                    }
                    $line[3] = format_text(implode(', ', $membernames));
                } else {
                    $line[3] = '-';
                }
            } else {
                $line[3] = '<div class="membershidden">'.format_text(get_string('membershidden', 'groupselect')).'</div>';
            }
            if ($isopen and !$accessall) { //!$hasgroup and
                if ($maxmembers and $maxmembers <= $usercount and !$ismember) {
                    $line[4] = '<div class="notavailable">'.get_string('notavailable', 'groupselect').'</div>'; // full - no more members
                } else if ($ismember) 
                {
                    $line[4] = format_text("<a title=\"$strsignout\" href=\"view.php?id=$cm->id&amp;signout=$group->id\">$strsignout</a>");
                } else if (!$ismember and !$hasgroup)
                {
                    $line[4] = format_text("<a title=\"$strsignup\" href=\"view.php?id=$cm->id&amp;signup=$group->id\">$strsignup</a> ");
                }
            }
            $data[] = $line;
        }

        $table = new html_table();
        $table->head  = array($strgroup, $strgroupdesc, $strcount, $strmembers);
        $table->size  = array('10%', '30%', '5%', '55%');
        $table->align = array('left', 'center', 'left', 'left');
        $table->width = '95%';
        $table->data  = $data;
        if ($isopen and !$accessall) {
            $table->head[]  = $straction;
            $table->size    = array('10%', '30%', '5%', '45%', '10%');
            $table->align[] = 'center';
        }
        echo html_writer::table($table);
        add_to_log($course->id, 'groupselect', 'view', "view.php?id=$cm->id", $groupselect->name, $cm->id, $USER->id);

    } else {
        echo $OUTPUT->notification(get_string('nogroups', 'groupselect'));
    }

    echo $OUTPUT->footer($course);
?>

