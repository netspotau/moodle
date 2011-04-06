<?php  // $Id: lib.php,v 1.1.2.6 2009/03/13 16:44:02 mudrd8mz Exp $

/**
 * Library of functions and constants of Group selection module
 *
 * @package mod/groupselect
 */

/**
 * Indicates API features that the groupselect supports.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_BACKUP_MOODLE2
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function groupselect_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}


/**
 * Is the given group selection open for students to select their group at the moment? 
 * 
 * @param object $groupselect Groupselect record
 * @return bool True if the group selection is open right now, false otherwise
 */
function groupselect_is_open($groupselect) {
    $now = time();
    return ($groupselect->timeavailable < $now AND ($groupselect->timedue == 0 or $groupselect->timedue > $now));
}


/**
 * Get the number of members in all groups the user can select from in this activity
 *
 * @param $cm Course module slot of the groupselect instance
 * @param $targetgrouping The id of grouping the user can select a group from
 * @return array of objects: [id] => object(->usercount ->id) where id is group id
 */
function groupselect_group_member_counts($cm, $targetgrouping=0) {
    global $CFG, $DB;

    if (empty($CFG->enablegroupings) or empty($cm->groupingid) or empty($targetgrouping)) {
        //all groups
        $sql = "SELECT g.id, COUNT(gm.userid) AS usercount
                  FROM {$CFG->prefix}groups_members gm
                       JOIN {$CFG->prefix}groups g ON g.id = gm.groupid
                 WHERE g.courseid = $cm->course
              GROUP BY g.id";  

    } else {
        $sql = "SELECT g.id, COUNT(gm.userid) AS usercount
                  FROM {$CFG->prefix}groups_members gm
                       JOIN {$CFG->prefix}groups g            ON g.id = gm.groupid
                       JOIN {$CFG->prefix}groupings_groups gg ON gg.groupid = g.id
                 WHERE g.courseid = $cm->course
                       AND gg.groupingid = $targetgrouping
              GROUP BY g.id";  
    }
    return $DB->get_records_sql($sql);
}


/**
 * Given an object containing all the necessary data, (defined by the form in mod.html) 
 * this function will create a new instance and return the id number of the new instance.
 *
 * @param object $groupselect Object containing all the necessary data defined by the form in mod_form.php
 * $return int The id of the newly created instance
 */
function groupselect_add_instance($groupselect) {
    global $DB;
    $groupselect->timecreated = time();
    $groupselect->timemodified = time();

    return $DB->insert_record('groupselect', $groupselect);
}


/**
 * Update an existing instance with new data.
 *
 * @param object $groupselect An object containing all the necessary data defined by the mod_form.php 
 * @return bool
 */
function groupselect_update_instance($groupselect) {
    global $CFG, $DB;
    $groupselect->timemodified = time();
    $groupselect->id = $groupselect->instance;

    return $DB->update_record('groupselect', $groupselect);
}


/**
 * Permanently delete the instance of the module and any data that depends on it.  
 *
 * @param int $id Instance id
 * @return bool
 */
function groupselect_delete_instance($id) {
    global $DB; 
    if (! $groupselect = $DB->get_record('groupselect', array('id' => $id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records('groupselect', array('id' => $groupselect->id))) {
        $result = false;
    }

    return $result;
}


/**
 * Returns the users with data in this module
 *
 * We have no data/users here but this must exists in every module
 * 
 * @param int $groupselectid 
 * @return bool
 */
function groupselect_get_participants($groupselectid) {
    return false;
}


/**
 * groupselect_get_view_actions
 *
 * @return array
 */
function groupselect_get_view_actions() {
    return array('view');
}

/**
 * groupselect_get_post_actions
 *
 * @return array
 */
function groupselect_get_post_actions() {
    return array('signup', 'signout');
}


/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function groupselect_reset_userdata($data) {
    return array();
}

function groupselect_save_limits ($groupselectid, $limits) {
    global $DB;
  $groupselectid = intval($groupselectid);
  $in = implode(',', array_keys($limits));
  # query for existing records which we can update or delete
  if ($rs = $DB->get_recordset_select('groupselect_limits', "groupselect = $groupselectid", 
                                        null, '', 'id, groupselect, groupid, lim')) {
    # array to store IDs of rows we want to delete
    $delete = array();
    foreach ($rs as $grouplimit) {
      if (isset($limits[$grouplimit->groupid])) {
        if ($limits[$grouplimit->groupid] != $grouplimit->lim) {
          # only need to update the row if the new limit is different to the
          # existing record
          $grouplimit->lim = $limits[$grouplimit->groupid];
          $DB->update_record('groupselect_limits', $grouplimit);
        }
      } else {
        # a limit for this groupid was left blank, so remove the row
        $delete[] = $grouplimit->id;
      }
      unset($limits[$grouplimit->groupid]);
    }

    if (count($delete)) {
      $delete_ids = implode(',', $delete);
      $DB->delete_records_select('groupselect_limits', "id IN ($delete_ids)");
    }
  }

  # insert all remaining limits
  foreach ($limits as $groupid => $lim) {
    $grouplimit = new object();
    $grouplimit->groupselect = $groupselectid;
    $grouplimit->groupid = $groupid;
    $grouplimit->lim = $lim;
    $DB->insert_record('groupselect_limits', $grouplimit);
  }
}

function groupselect_retrieve_limits_formdata ($groupselectid) {
    global $DB;
  $formdata = array();
  if ($grouplimits = $DB->get_records("groupselect_limits", array("groupselect" => $groupselectid))) {
    foreach ($grouplimits as $grouplimit) {
      $formdata['limit['.$grouplimit->groupid . ']'] = $grouplimit->lim;
    }
  }
  
  return $formdata;
}

function groupselect_get_limits ($groupselectid) {
    global $DB;
  $limits = array();
  if ($grouplimits = $DB->get_records("groupselect_limits", array("groupselect" => $groupselectid))) {
    foreach ($grouplimits as $grouplimit) {
      $limits[$grouplimit->groupid] = $grouplimit->lim;
    }
  }

  return $limits;
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $forumnode The node to add module settings to
 */
function groupselect_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $groupselectnode) {
    global $USER, $PAGE, $CFG, $DB, $OUTPUT;

    $groupselectobject = $DB->get_record("groupselect", array("id" => $PAGE->cm->instance));
    if (empty($PAGE->cm->context)) {
        $PAGE->cm->context = get_context_instance(CONTEXT_MODULE, $PAGE->cm->instance);
    }

    // for some actions you need to be enrolled, being admin is not enough sometimes here
    $enrolled = is_enrolled($PAGE->cm->context);

    $canmanage  = has_capability('moodle/course:managegroups', $PAGE->cm->context);

    if ($canmanage) {
        //$mode = $groupselectnode->add(get_string('subscriptionmode', 'forum'), null, navigation_node::TYPE_CONTAINER);
    } else if ($enrolled) {

    }
}


?>
