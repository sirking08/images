<?php
//namespace theme_klassroom\output;
//from fordson theme
use html_writer;
use moodle_url;
use context_course;

defined('MOODLE_INTERNAL') || die;
require_once('course_renderer.php');

class theme_klassroom_core_renderer extends \core_renderer {
	

	
	/** @var custom_menu_item language The language menu if created */
    protected $language = null;
	
	
	
	
	public function custom_menu($custommenuitems = '') {
        global $CFG;

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }	 
	
	protected function render_custom_menu(custom_menu $menu) {
        global $CFG;
		
		

        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if (!$menu->has_children() && !$haslangmenu) {
            return '';
        }

        if ($haslangmenu) {
            $strlang =  get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $menu->add($currentlang, new moodle_url(''), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        $content = '<ul class="navbar-nav navigation ">';
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }

        return $content.'</ul>';
    }
	
	protected function render_custom_menu_item(custom_menu_item $menunode, $level = 0 ) {
        static $submenucount = 0;

        $content = '';
        if ($menunode->has_children()) {

            if ($level == 1) {
                $class = 'dropdown';
            } else {
                $class = 'submenu';
            }

            if ($menunode === $this->language) {
                $class .= ' langmenu';
            }
            $content = html_writer::start_tag('li', array('class' => $class));
            // If the child has menus render it as a sub menu.
            $submenucount++;
            if ($menunode->get_url() !== null) {
                $url = $menunode->get_url();
            } else {
                $url = '#';
            }
            $content .= html_writer::start_tag('a', array('href'=>$url, 'class'=>'dropdown-item dropdown-toggle', 'role'=>'menuitem', 'data-toggle' => 'dropdown', 'title'=>$menunode->get_title()));
            $content .= $menunode->get_text();
            if ($level == 1) {
                //$content .= '<b class="caret"></b>';
            }
            $content .= '</a>';
            $content .= '<ul class="dropdown-list">';
            foreach ($menunode->get_children() as $menunode) {
                $content .= $this->render_custom_menu_item($menunode, 0);
            }
            $content .= '</ul>';
        } else {
            // The node doesn't have children so produce a final menuitem.
            // Also, if the node's text matches '####', add a class so we can treat it as a divider.
            if (preg_match("/^#+$/", $menunode->get_text())) {
                // This is a divider.
                //$content = '<li class="divider">&nbsp;</li>';
            } else {
                $content = '<li>';
                if ($menunode->get_url() !== null) {
                    $url = $menunode->get_url();
                } else {
                    $url = '#';
                }
                $content .= html_writer::link($url, $menunode->get_text(), array('title' => $menunode->get_title()));
                $content .= '</li>';
            }
        }
        return $content;
    }
}
//from fordson theme
//courseactivities_menu required in teacherdash
    protected function render_courseactivities_menu(custom_menu $menu) {
        global $CFG;
        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('theme_fordson/activitygroups', $context);
        }
        return $content;
    }
    public function courseactivities_menu() {
        global $PAGE, $COURSE, $OUTPUT, $CFG;
        $menu = new custom_menu();
        $context = $this->page->context;
        if (isset($COURSE->id) && $COURSE->id > 1) {
            $branchtitle = get_string('courseactivities', 'theme_fordson');
            $branchlabel = $branchtitle;
            $branchurl = new moodle_url('#');
            $branch = $menu->add($branchlabel, $branchurl, $branchtitle, 10002);
            $data = theme_fordson_get_course_activities();
            foreach ($data as $modname => $modfullname) {
                if ($modname === 'resources') {
                    $branch->add($modfullname, new moodle_url('/course/resources.php', array(
                        'id' => $PAGE->course->id
                    )));
                }
                else {
                    $branch->add($modfullname, new moodle_url('/mod/' . $modname . '/index.php', array(
                        'id' => $PAGE->course->id
                    )));
                }
            }
        }
        return $this->render_courseactivities_menu($menu);
    }
	
    public function teacherdashmenu() {
        global $PAGE, $COURSE, $CFG, $DB, $OUTPUT;
        $course = $this->page->course;
        $context = context_course::instance($course->id);
        $showincourseonly = isset($COURSE->id) && $COURSE->id > 1 && $PAGE->theme->settings->coursemanagementtoggle && isloggedin() && !isguestuser();
        $haspermission = has_capability('enrol/category:config', $context) && $PAGE->theme->settings->coursemanagementtoggle && isset($COURSE->id) && $COURSE->id > 1;
        $togglebutton = '';
        $togglebuttonstudent = '';
        $hasteacherdash = '';
        $hasstudentdash = '';
        $globalhaseasyenrollment = enrol_get_plugin('easy');
        $coursehaseasyenrollment = '';
        if ($globalhaseasyenrollment) {
            $coursehaseasyenrollment = $DB->record_exists('enrol', array(
                'courseid' => $COURSE->id,
                'enrol' => 'easy'
            ));
            $easyenrollinstance = $DB->get_record('enrol', array(
                'courseid' => $COURSE->id,
                'enrol' => 'easy'
            ));
        }
        if ($coursehaseasyenrollment && isset($COURSE->id) && $COURSE->id > 1) {
            $easycodetitle = get_string('header_coursecodes', 'enrol_easy');
            $easycodelink = new moodle_url('/enrol/editinstance.php', array(
                'courseid' => $PAGE->course->id,
                'id' => $easyenrollinstance->id,
                'type' => 'easy'
            ));
        }
        if (isloggedin() && ISSET($COURSE->id) && $COURSE->id > 1) {
            $course = $this->page->course;
            $context = context_course::instance($course->id);
            $hasteacherdash = has_capability('moodle/course:viewhiddenactivities', $context);
            $hasstudentdash = !has_capability('moodle/course:viewhiddenactivities', $context);
            if (has_capability('moodle/course:viewhiddenactivities', $context)) {
                $togglebutton = get_string('coursemanagementbutton', 'theme_klassroom');
            }
            else {
                $togglebuttonstudent = get_string('studentdashbutton', 'theme_klassroom');
            }
        }
        $siteadmintitle = get_string('siteadminquicklink', 'theme_klassroom');
        $siteadminurl = new moodle_url('/admin/search.php');
        $hasadminlink = has_capability('moodle/site:configview', $context);
        $course = $this->page->course;
        // Send to template.
        $dashmenu = ['showincourseonly' => $showincourseonly, 'togglebutton' => $togglebutton, 'togglebuttonstudent' => $togglebuttonstudent, 'hasteacherdash' => $hasteacherdash, 'hasstudentdash' => $hasstudentdash, 'haspermission' => $haspermission, 'hasadminlink' => $hasadminlink, 'siteadmintitle' => $siteadmintitle, 'siteadminurl' => $siteadminurl, ];
        // Attach easy enrollment links if active.
        if ($globalhaseasyenrollment && $coursehaseasyenrollment) {
            $dashmenu['dashmenu'][] = array(
                'haseasyenrollment' => $coursehaseasyenrollment,
                'title' => $easycodetitle,
                'url' => $easycodelink
            );
        }
        return $this->render_from_template('theme_klassroom/teacherdashmenu', $dashmenu);
    }

    public function teacherdash() {
        global $PAGE, $COURSE, $CFG, $DB, $OUTPUT, $USER;
        require_once ($CFG->dirroot . '/completion/classes/progress.php');
        $togglebutton = '';
        $togglebuttonstudent = '';
        $hasteacherdash = '';
        $hasstudentdash = '';
        $haseditcog = $PAGE->theme->settings->courseeditingcog;
        $editcog = html_writer::div($this->context_header_settings_menu() , 'pull-xs-right context-header-settings-menu');
        if (isloggedin() && ISSET($COURSE->id) && $COURSE->id > 1) {
            $course = $this->page->course;
            $context = context_course::instance($course->id);
            $hasteacherdash = has_capability('moodle/course:viewhiddenactivities', $context);
            $hasstudentdash = !has_capability('moodle/course:viewhiddenactivities', $context);
            if (has_capability('moodle/course:viewhiddenactivities', $context)) {
                $togglebutton = get_string('coursemanagementbutton', 'theme_klassroom');
            }
            else {
                $togglebuttonstudent = get_string('studentdashbutton', 'theme_klassroom');
            }
        }
        $course = $this->page->course;
        $context = context_course::instance($course->id);
        $coursemanagementmessage = (empty($PAGE->theme->settings->coursemanagementtextbox)) ? false : format_text($PAGE->theme->settings->coursemanagementtextbox);
        $courseactivities = $this->courseactivities_menu();
        $showincourseonly = isset($COURSE->id) && $COURSE->id > 1 && $PAGE->theme->settings->coursemanagementtoggle && isloggedin() && !isguestuser();
        $globalhaseasyenrollment = enrol_get_plugin('easy');
        $coursehaseasyenrollment = '';
        if ($globalhaseasyenrollment) {
            $coursehaseasyenrollment = $DB->record_exists('enrol', array(
                'courseid' => $COURSE->id,
                'enrol' => 'easy'
            ));
            $easyenrollinstance = $DB->get_record('enrol', array(
                'courseid' => $COURSE->id,
                'enrol' => 'easy'
            ));
        }
        // Link catagories.
        $haspermission = has_capability('enrol/category:config', $context) && $PAGE->theme->settings->coursemanagementtoggle && isset($COURSE->id) && $COURSE->id > 1;
        $userlinks = get_string('userlinks', 'theme_klassroom');
        $userlinksdesc = get_string('userlinks_desc', 'theme_klassroom');
        $qbank = get_string('qbank', 'theme_klassroom');
        $qbankdesc = get_string('qbank_desc', 'theme_klassroom');
        $badges = get_string('badges', 'theme_klassroom');
        $badgesdesc = get_string('badges_desc', 'theme_klassroom');
        $coursemanage = get_string('coursemanage', 'theme_klassroom');
        $coursemanagedesc = get_string('coursemanage_desc', 'theme_klassroom');
        $coursemanagementmessage = (empty($PAGE->theme->settings->coursemanagementtextbox)) ? false : format_text($PAGE->theme->settings->coursemanagementtextbox, FORMAT_HTML, array(
            'noclean' => true
        ));
        $studentdashboardtextbox = (empty($PAGE->theme->settings->studentdashboardtextbox)) ? false : format_text($PAGE->theme->settings->studentdashboardtextbox, FORMAT_HTML, array(
            'noclean' => true
        ));
        // User links.
        if ($coursehaseasyenrollment && isset($COURSE->id) && $COURSE->id > 1) {
            $easycodetitle = get_string('header_coursecodes', 'enrol_easy');
            $easycodelink = new moodle_url('/enrol/editinstance.php', array(
                'courseid' => $PAGE->course->id,
                'id' => $easyenrollinstance->id,
                'type' => 'easy'
            ));
        }
        $gradestitle = get_string('gradebooksetup', 'grades');
        $gradeslink = new moodle_url('/grade/edit/tree/index.php', array(
            'id' => $PAGE->course->id
        ));
        $gradebooktitle = get_string('gradebook', 'grades');
        $gradebooklink = new moodle_url('/grade/report/grader/index.php', array(
            'id' => $PAGE->course->id
        ));
        $participantstitle = ($PAGE->theme->settings->studentdashboardtextbox == 1) ? false : get_string('participants', 'moodle');
        $participantslink = new moodle_url('/user/index.php', array(
            'id' => $PAGE->course->id
        ));
        (empty($participantstitle)) ? false : get_string('participants', 'moodle');
        $activitycompletiontitle = get_string('activitycompletion', 'completion');
        $activitycompletionlink = new moodle_url('/report/progress/index.php', array(
            'course' => $PAGE->course->id
        ));
        $grouptitle = get_string('groups', 'group');
        $grouplink = new moodle_url('/group/index.php', array(
            'id' => $PAGE->course->id
        ));
        $enrolmethodtitle = get_string('enrolmentinstances', 'enrol');
        $enrolmethodlink = new moodle_url('/enrol/instances.php', array(
            'id' => $PAGE->course->id
        ));
        // User reports.
        $logstitle = get_string('logs', 'moodle');
        $logslink = new moodle_url('/report/log/index.php', array(
            'id' => $PAGE->course->id
        ));
        $livelogstitle = get_string('loglive:view', 'report_loglive');
        $livelogslink = new moodle_url('/report/loglive/index.php', array(
            'id' => $PAGE->course->id
        ));
        $participationtitle = get_string('participation:view', 'report_participation');
        $participationlink = new moodle_url('/report/participation/index.php', array(
            'id' => $PAGE->course->id
        ));
        $activitytitle = get_string('outline:view', 'report_outline');
        $activitylink = new moodle_url('/report/outline/index.php', array(
            'id' => $PAGE->course->id
        ));
        $completionreporttitle = get_string('coursecompletion', 'completion');
        $completionreportlink = new moodle_url('/report/completion/index.php', array(
            'course' => $PAGE->course->id
        ));
        // Questionbank.
        $qbanktitle = get_string('questionbank', 'question');
        $qbanklink = new moodle_url('/question/edit.php', array(
            'courseid' => $PAGE->course->id
        ));
        $qcattitle = get_string('questioncategory', 'question');
        $qcatlink = new moodle_url('/question/category.php', array(
            'courseid' => $PAGE->course->id
        ));
        $qimporttitle = get_string('import', 'question');
        $qimportlink = new moodle_url('/question/import.php', array(
            'courseid' => $PAGE->course->id
        ));
        $qexporttitle = get_string('export', 'question');
        $qexportlink = new moodle_url('/question/export.php', array(
            'courseid' => $PAGE->course->id
        ));
        // Manage course.
        $courseadmintitle = get_string('courseadministration', 'moodle');
        $courseadminlink = new moodle_url('/course/admin.php', array(
            'courseid' => $PAGE->course->id
        ));
        $coursecompletiontitle = get_string('editcoursecompletionsettings', 'completion');
        $coursecompletionlink = new moodle_url('/course/completion.php', array(
            'id' => $PAGE->course->id
        ));
        $competencytitle = get_string('competencies', 'competency');
        $competencyurl = new moodle_url('/admin/tool/lp/coursecompetencies.php', array(
            'courseid' => $PAGE->course->id
        ));
        $courseresettitle = get_string('reset', 'moodle');
        $courseresetlink = new moodle_url('/course/reset.php', array(
            'id' => $PAGE->course->id
        ));
        $coursebackuptitle = get_string('backup', 'moodle');
        $coursebackuplink = new moodle_url('/backup/backup.php', array(
            'id' => $PAGE->course->id
        ));
        $courserestoretitle = get_string('restore', 'moodle');
        $courserestorelink = new moodle_url('/backup/restorefile.php', array(
            'contextid' => $PAGE->context->id
        ));
        $courseimporttitle = get_string('import', 'moodle');
        $courseimportlink = new moodle_url('/backup/import.php', array(
            'id' => $PAGE->course->id
        ));
        $courseedittitle = get_string('editcoursesettings', 'moodle');
        $courseeditlink = new moodle_url('/course/edit.php', array(
            'id' => $PAGE->course->id
        ));
        $badgemanagetitle = get_string('managebadges', 'badges');
        $badgemanagelink = new moodle_url('/badges/index.php?type=2', array(
            'id' => $PAGE->course->id
        ));
        $badgeaddtitle = get_string('newbadge', 'badges');
        $badgeaddlink = new moodle_url('/badges/newbadge.php?type=2', array(
            'id' => $PAGE->course->id
        ));
        $recyclebintitle = get_string('pluginname', 'tool_recyclebin');
        $recyclebinlink = new moodle_url('/admin/tool/recyclebin/index.php', array(
            'contextid' => $PAGE->context->id
        ));
        $filtertitle = get_string('filtersettings', 'filters');
        $filterlink = new moodle_url('/filter/manage.php', array(
            'contextid' => $PAGE->context->id
        ));
        $eventmonitoringtitle = get_string('managesubscriptions', 'tool_monitor');
        $eventmonitoringlink = new moodle_url('/admin/tool/monitor/managerules.php', array(
            'courseid' => $PAGE->course->id
        ));
        $copycoursetitle = get_string('copycourse', 'moodle');
        $copycourselink = new moodle_url('/backup/copy.php', array(
            'id' => $PAGE->course->id
        ));

        // Student Dash
        if (\core_completion\progress::get_course_progress_percentage($PAGE->course)) {
            $comppc = \core_completion\progress::get_course_progress_percentage($PAGE->course);
            $comppercent = number_format($comppc, 0);
        }
        else {
            $comppercent = 0;
        }

        $progresschartcontext = ['progress' => $comppercent];
        $progress = $this->render_from_template('theme_klassroom/progress-bar', $progresschartcontext);

        $gradeslinkstudent = new moodle_url('/grade/report/user/index.php', array(
            'id' => $PAGE->course->id
        ));
        $hascourseinfogroup = array(
            'title' => get_string('courseinfo', 'theme_klassroom') ,
            'icon' => 'map'
        );
        $summary = theme_klassroom_strip_html_tags($COURSE->summary);
        $summarytrim = theme_klassroom_course_trim_char($summary, 300);
        $courseinfo = array(
            array(
                'content' => format_text($summarytrim) ,
            )
        );
        $hascoursestaff = array(
            'title' => get_string('coursestaff', 'theme_klassroom') ,
            'icon' => 'users'
        );
        $courseteachers = array();
        $courseother = array();

        $showonlygroupteachers = !empty(groups_get_all_groups($course->id, $USER->id)) && $PAGE->theme->settings->showonlygroupteachers == 1;
        if ($showonlygroupteachers) {
            $groupids = array();
            $studentgroups = groups_get_all_groups($course->id, $USER->id);
            foreach ($studentgroups as $grp) {
                $groupids[] = $grp->id;
            }
        }

        // If you created custom roles, please change the shortname value to match the name of your role.  This is teacher.
        $role = $DB->get_record('role', array(
            'shortname' => 'editingteacher'
        ));
        if ($role) {
            $context = context_course::instance($PAGE->course->id);
            $teachers = get_role_users($role->id, $context, false, 'u.id, u.firstname, u.middlename, u.lastname, u.alternatename,
                    u.firstnamephonetic, u.lastnamephonetic, u.email, u.picture, u.maildisplay,
                    u.imagealt');
            foreach ($teachers as $staff) {
                if ($showonlygroupteachers) {
                    $staffgroups = groups_get_all_groups($course->id, $staff->id);
                    $found = false;
                    foreach ($staffgroups as $grp) {
                        if (in_array($grp->id, $groupids)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        continue;
                    }
                }
                $picture = $OUTPUT->user_picture($staff, array(
                    'size' => 50
                ));
                $messaging = new moodle_url('/message/index.php', array(
                    'id' => $staff->id
                ));
                $hasmessaging = $CFG->messaging == 1;
                $courseteachers[] = array(
                    'name' => $staff->firstname . ' ' . $staff->lastname . ' ' . $staff->alternatename,
                    'email' => $staff->email,
                    'picture' => $picture,
                    'messaging' => $messaging,
                    'hasmessaging' => $hasmessaging,
                    'hasemail' => $staff->maildisplay
                );
            }
        }

        // If you created custom roles, please change the shortname value to match the name of your role.  This is non-editing teacher.
        $role = $DB->get_record('role', array(
            'shortname' => 'teacher'
        ));
        if ($role) {
            $context = context_course::instance($PAGE->course->id);
            $teachers = get_role_users($role->id, $context, false, 'u.id, u.firstname, u.middlename, u.lastname, u.alternatename,
                    u.firstnamephonetic, u.lastnamephonetic, u.email, u.picture, u.maildisplay,
                    u.imagealt');
            foreach ($teachers as $staff) {
                if ($showonlygroupteachers) {
                    $staffgroups = groups_get_all_groups($course->id, $staff->id);
                    $found = false;
                    foreach ($staffgroups as $grp) {
                        if (in_array($grp->id, $groupids)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        continue;
                    }
                }
                $picture = $OUTPUT->user_picture($staff, array(
                    'size' => 50
                ));
                $messaging = new moodle_url('/message/index.php', array(
                    'id' => $staff->id
                ));
                $hasmessaging = $CFG->messaging == 1;
                $courseother[] = array(
                    'name' => $staff->firstname . ' ' . $staff->lastname,
                    'email' => $staff->email,
                    'picture' => $picture,
                    'messaging' => $messaging,
                    'hasmessaging' => $hasmessaging,
                    'hasemail' => $staff->maildisplay
                );
            }
        }
        $activitylinkstitle = get_string('activitylinkstitle', 'theme_klassroom');
        $activitylinkstitle_desc = get_string('activitylinkstitle_desc', 'theme_klassroom');
        $mygradestext = get_string('mygradestext', 'theme_klassroom');
        $studentcoursemanage = get_string('courseadministration', 'moodle');
        // Permissionchecks for teacher access.
        $hasquestionpermission = has_capability('moodle/question:add', $context);
        $hasbadgepermission = has_capability('moodle/badges:awardbadge', $context);
        $hascoursepermission = has_capability('moodle/backup:backupcourse', $context);
        $hasuserpermission = has_capability('moodle/course:viewhiddenactivities', $context);
        $hasgradebookshow = $PAGE->course->showgrades == 1 && $PAGE->theme->settings->showstudentgrades == 1;
        $hascompletionshow = $PAGE->course->enablecompletion == 1 && $PAGE->theme->settings->showstudentcompletion == 1;
        $hascourseadminshow = $PAGE->theme->settings->showcourseadminstudents == 1;
        $hascompetency = get_config('core_competency', 'enabled');
        // Send to template.
        $haseditcog = $PAGE->theme->settings->courseeditingcog;
        $editcog = html_writer::div($this->context_header_settings_menu() , 'pull-xs-right context-header-settings-menu');
        $dashlinks = [
            'showincourseonly' => $showincourseonly, 
            'haspermission' => $haspermission, 
            'courseactivities' => $courseactivities, 
            'togglebutton' => $togglebutton, 
            'togglebuttonstudent' => $togglebuttonstudent, 
            'userlinkstitle' => $userlinks, 
            'userlinksdesc' => $userlinksdesc, 
            'qbanktitle' => $qbank, 
            'activitylinkstitle' => $activitylinkstitle, 
            'activitylinkstitle_desc' => $activitylinkstitle_desc, 
            'qbankdesc' => $qbankdesc, 
            'badgestitle' => $badges, 
            'badgesdesc' => $badgesdesc, 
            'coursemanagetitle' => $coursemanage, 
            'coursemanagedesc' => $coursemanagedesc, 
            'coursemanagementmessage' => $coursemanagementmessage, 
            'progress' => $progress, 
            'gradeslink' => $gradeslink, 
            'gradeslinkstudent' => $gradeslinkstudent, 
            'hascourseinfogroup' => $hascourseinfogroup, 
            'courseinfo' => $courseinfo, 
            'hascoursestaffgroup' => $hascoursestaff, 
            'courseteachers' => $courseteachers, 
            'courseother' => $courseother, 
            'mygradestext' => $mygradestext, 
            'studentdashboardtextbox' => $studentdashboardtextbox, 
            'hasteacherdash' => $hasteacherdash, 
            'haseditcog'=>$haseditcog, 
            'editcog'=> $editcog, 
            'teacherdash' => array(
                'hasquestionpermission' => $hasquestionpermission,
                'hasbadgepermission' => $hasbadgepermission,
                'hascoursepermission' => $hascoursepermission,
                'hasuserpermission' => $hasuserpermission
            ) , 
            'hasstudentdash' => $hasstudentdash, 
            'hasgradebookshow' => $hasgradebookshow, 
            'hascompletionshow' => $hascompletionshow, 
            'studentcourseadminlink' => $courseadminlink, 
            'studentcoursemanage' => $studentcoursemanage, 
            'hascourseadminshow' => $hascourseadminshow, 
            'hascompetency' => $hascompetency, 
            'competencytitle' => $competencytitle, 
            'competencyurl' => $competencyurl, 
            'dashlinks' => array(
                array(
                    'hasuserlinks' => $gradebooktitle,
                    'title' => $gradebooktitle,
                    'url' => $gradebooklink
                ) ,
                array(
                    'hasuserlinks' => $participantstitle,
                    'title' => $participantstitle,
                    'url' => $participantslink
                ) ,
                array(
                    'hasuserlinks' => $grouptitle,
                    'title' => $grouptitle,
                    'url' => $grouplink
                ) ,
                array(
                    'hasuserlinks' => $enrolmethodtitle,
                    'title' => $enrolmethodtitle,
                    'url' => $enrolmethodlink
                ) ,
                array(
                    'hasuserlinks' => $activitycompletiontitle,
                    'title' => $activitycompletiontitle,
                    'url' => $activitycompletionlink
                ) ,
                array(
                    'hasuserlinks' => $completionreporttitle,
                    'title' => $completionreporttitle,
                    'url' => $completionreportlink
                ) ,
                array(
                    'hasuserlinks' => $logstitle,
                    'title' => $logstitle,
                    'url' => $logslink
                ) ,
                array(
                    'hasuserlinks' => $livelogstitle,
                    'title' => $livelogstitle,
                    'url' => $livelogslink
                ) ,
                array(
                    'hasuserlinks' => $participationtitle,
                    'title' => $participationtitle,
                    'url' => $participationlink
                ) ,
                array(
                    'hasuserlinks' => $activitytitle,
                    'title' => $activitytitle,
                    'url' => $activitylink
                ) ,
                array(
                    'hasqbanklinks' => $qbanktitle,
                    'title' => $qbanktitle,
                    'url' => $qbanklink
                ) ,
                array(
                    'hasqbanklinks' => $qcattitle,
                    'title' => $qcattitle,
                    'url' => $qcatlink
                ) ,
                array(
                    'hasqbanklinks' => $qimporttitle,
                    'title' => $qimporttitle,
                    'url' => $qimportlink
                ) ,
                array(
                    'hasqbanklinks' => $qexporttitle,
                    'title' => $qexporttitle,
                    'url' => $qexportlink
                ) ,
                array(
                    'hascoursemanagelinks' => $courseedittitle,
                    'title' => $courseedittitle,
                    'url' => $courseeditlink
                ) ,
                array(
                    'hascoursemanagelinks' => $gradestitle,
                    'title' => $gradestitle,
                    'url' => $gradeslink
                ) ,
                array(
                    'hascoursemanagelinks' => $coursecompletiontitle,
                    'title' => $coursecompletiontitle,
                    'url' => $coursecompletionlink
                ) ,
                array(
                    'hascoursemanagelinks' => $hascompetency,
                    'title' => $competencytitle,
                    'url' => $competencyurl
                ) ,
                array(
                    'hascoursemanagelinks' => $courseadmintitle,
                    'title' => $courseadmintitle,
                    'url' => $courseadminlink
                ) ,
                array(
                    'hascoursemanagelinks' => $copycoursetitle,
                    'title' => $copycoursetitle,
                    'url' => $copycourselink
                ) ,
                array(
                    'hascoursemanagelinks' => $courseresettitle,
                    'title' => $courseresettitle,
                    'url' => $courseresetlink
                ) ,
                array(
                    'hascoursemanagelinks' => $coursebackuptitle,
                    'title' => $coursebackuptitle,
                    'url' => $coursebackuplink
                ) ,
                array(
                    'hascoursemanagelinks' => $courserestoretitle,
                    'title' => $courserestoretitle,
                    'url' => $courserestorelink
                ) ,
                array(
                    'hascoursemanagelinks' => $courseimporttitle,
                    'title' => $courseimporttitle,
                    'url' => $courseimportlink
                ) ,
                array(
                    'hascoursemanagelinks' => $recyclebintitle,
                    'title' => $recyclebintitle,
                    'url' => $recyclebinlink
                ) ,
                array(
                    'hascoursemanagelinks' => $filtertitle,
                    'title' => $filtertitle,
                    'url' => $filterlink
                ) ,
                array(
                    'hascoursemanagelinks' => $eventmonitoringtitle,
                    'title' => $eventmonitoringtitle,
                    'url' => $eventmonitoringlink
                ) ,
                array(
                    'hasbadgelinks' => $badgemanagetitle,
                    'title' => $badgemanagetitle,
                    'url' => $badgemanagelink
                ) ,
                array(
                    'hasbadgelinks' => $badgeaddtitle,
                    'title' => $badgeaddtitle,
                    'url' => $badgeaddlink
                ) ,
            ) ,
            ];
        // Attach easy enrollment links if active.
        if ($globalhaseasyenrollment && $coursehaseasyenrollment) {
            $dashlinks['dashlinks'][] = array(
                'haseasyenrollment' => $coursehaseasyenrollment,
                'title' => $easycodetitle,
                'url' => $easycodelink
            );
        }
        return $this->render_from_template('theme_klassroom/teacherdash', $dashlinks);
    }
