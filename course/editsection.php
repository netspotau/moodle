<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit the introduction of a section
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

require_once("../config.php");
require_once("lib.php");
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/conditionlib_section.php');

require_once('editsection_form.php');

$id = required_param('id',PARAM_INT);    // Week/topic ID

$PAGE->set_url('/course/editsection.php', array('id'=>$id));

$section = $DB->get_record('course_sections', array('id' => $id), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/course:update', $context);

$editoroptions = array('context'=>$context ,'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
$section = file_prepare_standard_editor($section, 'summary', $editoroptions, $context, 'course', 'section', $section->id);
$section->usedefaultname = (is_null($section->name));
if (!empty($CFG->enableavailability)) {
    if ($data_dt = $DB->get_record('course_sections_availability_dt', array('coursesectionid' => $section->id), '*'))
    {
        $section->groupingid         = $data_dt->groupingid;
        $section->availablefrom      = $data_dt->availablefrom;
        $section->availableuntil     = $data_dt->availableuntil;
        $section->showavailability   = $data_dt->showavailability;
        $section->coursesectionid    = $section->id;
    }

    $conditions = $DB->get_records_sql($sql="
SELECT
csacg.id as csacgid, gi.*,csacg.sourcecmid as sourcecmid,csacg.requiredcompletion as requiredcompletion, csacg.gradeitemid as gradeitemid,
csacg.grademin as conditiongrademin, csacg.grademax as conditiongrademax
FROM
{course_sections_availability_cg} csacg
LEFT JOIN {grade_items} gi ON gi.id=csacg.gradeitemid
WHERE
coursesectionid=?",array($section->id));
    $countcompletions = $countgrades = 0;
    foreach ($conditions as $condition) {
        if (!is_null($condition->sourcecmid)) {
            $section->conditioncompletiongroup[$countcompletions]['conditionsourcecmid'] = $condition->sourcecmid;
            $section->conditioncompletiongroup[$countcompletions]['conditionrequiredcompletion'] = $condition->requiredcompletion;
            $countcompletions++;
        } else {
            $minmax = new stdClass;
            $minmax->min = $condition->conditiongrademin;
            $minmax->max = $condition->conditiongrademax;
            //$minmax->name = condition_info_section::get_grade_name($condition);
            $section->conditiongradegroup[$condition->gradeitemid] = $minmax;
            $countgrades++;
        }
    }
    $section->conditioncompletionrepeats = $countcompletions;
    $section->conditiongraderepeats = $countgrades;
}

$mform = new editsection_form(null, array('course'=>$course, 'editoroptions'=>$editoroptions));
$mform->set_data($section); // set current value
$mform->completionrepeats = $countcompletions;
$mform->graderepeats = $countgrades;
$mform->cs = $section;
$mform->showavailability = isset($section->showavailability) ? $section->showavailability  : null;

/// If data submitted, then process and store.
if ($mform->is_cancelled()){
    redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);

} else if ($data = $mform->get_data()) {
    if (empty($data->usedefaultname)) {
        $section->name = $data->name;
    } else {
        $section->name = null;
    }
    $data = file_postupdate_standard_editor($data, 'summary', $editoroptions, $context, 'course', 'section', $section->id);
    $section->summary = $data->summary;
    $section->summaryformat = $data->summaryformat;
    $DB->update_record('course_sections', $section);
    if (!empty($CFG->enableavailability)) {
        //inserting or updating date-time and show avalability
        if ($data_dt!==false){
            $data_dt->groupingid         = $data->groupingid;
            $data_dt->availablefrom      = $data->availablefrom;
            $data_dt->availableuntil     = $data->availableuntil;
            $data_dt->showavailability   = $data->showavailability;
            $data_dt->coursesectionid    = $section->id;
            $DB->update_record('course_sections_availability_dt', $data_dt);
        } else {
            $data_dt = new stdClass;
            $data_dt->groupingid         = $data->groupingid;
            $data_dt->availablefrom      = $data->availablefrom;
            $data_dt->availableuntil     = $data->availableuntil;
            $data_dt->showavailability   = $data->showavailability;
            $data_dt->coursesectionid    = $section->id;
            $DB->insert_record_raw('course_sections_availability_dt', $data_dt);
        }
        //updating grade & completion conditions table
        //first let's delete existing conditions for his section from db
        $DB->delete_records('course_sections_availability_cg', array('coursesectionid' => $section->id));
        //now insert new conditions received from user
        foreach ($data->conditiongradegroup as $groupvalue){
            if ($groupvalue['conditiongradeitemid']>0){
                $data_cg = new stdClass;
                $data_cg->coursesectionid = $section->id;
                $data_cg->gradeitemid = $groupvalue['conditiongradeitemid'];
                $data_cg->grademin = $groupvalue['conditiongrademin'];
                $data_cg->grademax = $groupvalue['conditiongrademax'];
                $DB->insert_record_raw('course_sections_availability_cg', $data_cg);
            }
        }
        foreach ($data->conditioncompletiongroup as $groupvalue){
            if ($groupvalue['conditionsourcecmid']>0){
                $data_cg = new stdClass;
                $data_cg->coursesectionid = $section->id;
                $data_cg->sourcecmid = $groupvalue['conditionsourcecmid'];
                $data_cg->requiredcompletion = $groupvalue['conditionrequiredcompletion'];
                $DB->insert_record_raw('course_sections_availability_cg', $data_cg);
            }
        }

    }
    
    add_to_log($course->id, "course", "editsection", "editsection.php?id=$section->id", "$section->section");
    $PAGE->navigation->clear_cache();
    redirect("view.php?id=$course->id");
}

$sectionname  = get_section_name($course, $section);
$stredit      = get_string('edita', '', " $sectionname");
$strsummaryof = get_string('summaryof', '', " $sectionname");

$PAGE->set_title($stredit);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($stredit);
echo $OUTPUT->header();

echo $OUTPUT->heading($strsummaryof);

$mform->display();
echo $OUTPUT->footer();
