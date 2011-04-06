<?php // $Id: mod_form.php,v 1.1.2.3 2009/03/06 15:47:04 mudrd8mz Exp $
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_groupselect_limits_form extends moodleform {

    function mod_groupselect_limits_form ($groups) {
      $this->groups = $groups;
      parent::moodleform();
    }

    function definition() {
        global $COURSE;

        $mform    =& $this->_form;
        
        $strlimit = get_string('limit', 'groupselect');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        foreach ($this->groups as $group) {
          $elname = 'limit[' . $group->id . ']';
          $mform->addElement('text', $elname, $group->name . ' ' . $strlimit, array('size' => 4));
          $mform->setType($elname, PARAM_INT);
        }

        $this->add_action_buttons();
    }
}
?>
