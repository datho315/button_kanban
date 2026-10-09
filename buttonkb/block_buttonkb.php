<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Floating Kanban overview widget for the site home and user Dashboard.
 *
 * @package    block_buttonkb
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_buttonkb extends block_base {

    /**
     * Initialise the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_buttonkb');
    }

    /**
     * The floating widget has its own heading.
     *
     * @return bool
     */
    public function hide_header() {
        return true;
    }

    /**
     * Only one copy of the widget is required on each page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Restrict placement to the site home and personal pages.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'my' => true,
            'site-index' => true,
        ];
    }

    /**
     * Return the current user's Kanban summary widget.
     *
     * @return stdClass
     */
    public function get_content() {
        global $DB, $OUTPUT, $USER;

        if (isset($this->content)) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (isguestuser() || empty($USER->id)) {
            return $this->content;
        }

        $courses = enrol_get_users_courses($USER->id, true, 'id, fullname', 'fullname ASC');
        $courseids = array_keys($courses);
        $assignmentcount = $this->count_user_kanban_cards($courseids, $USER->id);
        $deadlines = $this->get_upcoming_linked_deadlines($courseids);

        $widgetid = 'buttonkb-' . (int)$this->instance->id;
        $this->page->requires->js_call_amd('block_buttonkb/button', 'init', [$widgetid]);

        $calendarimage = html_writer::empty_tag('img', [
            'src' => $OUTPUT->image_url('calendar', 'block_buttonkb')->out(false),
            'class' => 'buttonkb-calendar-icon',
            'alt' => '',
            'aria-hidden' => 'true',
        ]);
        $button = html_writer::tag('button', $calendarimage, [
                'type' => 'button',
                'class' => 'buttonkb-trigger',
                'aria-expanded' => 'false',
                'aria-controls' => $widgetid . '-panel',
                'aria-label' => get_string('open_widget', 'block_buttonkb'),
            ]);

        $this->content->text = html_writer::div(
            $button . $this->render_panel($widgetid, $assignmentcount, count($courseids), $deadlines),
            'buttonkb-widget',
            ['id' => $widgetid]
        );
        return $this->content;
    }

    /**
     * Count Kanban cards assigned to the user or visible to their course groups.
     *
     * @param int[] $courseids Enrolled course IDs.
     * @param int $userid User ID.
     * @return int
     */
    private function count_user_kanban_cards(array $courseids, $userid) {
        global $DB;

        if (empty($courseids)) {
            return 0;
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        $params['groupuserid'] = (int)$userid;
        $params['assigneeuserid'] = (int)$userid;
        $params['legacyuserid'] = (int)$userid;

        return (int)$DB->get_field_sql(
            "SELECT COUNT(DISTINCT card.id)
               FROM {kanban_cards} card
               JOIN {kanban} kanban ON kanban.id = card.kanbanid
          LEFT JOIN {kanban_card_assignees} assignee ON assignee.cardid = card.id
          LEFT JOIN {groups_members} groupmember
                 ON groupmember.groupid = card.groupid AND groupmember.userid = :groupuserid
              WHERE kanban.course $insql
                AND (card.groupid = 0 OR assignee.userid = :assigneeuserid
                     OR card.assigned_to = :legacyuserid OR groupmember.userid IS NOT NULL)",
            $params
        );
    }

    /**
     * Get the nearest upcoming due date from linked Moodle assignments per course.
     *
     * @param int[] $courseids Enrolled course IDs.
     * @return array
     */
    private function get_upcoming_linked_deadlines(array $courseids) {
        global $DB;

        if (empty($courseids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        $params['now'] = time();
        $records = $DB->get_records_sql(
            "SELECT assignment.id, kanban.course, course.fullname AS coursename, assignment.name AS assignmentname,
                    assignment.duedate
               FROM {kanban} kanban
               JOIN {assign} assignment ON assignment.id = kanban.assignmentid
               JOIN {course} course ON course.id = kanban.course
              WHERE kanban.course $insql
                AND kanban.assignmentid > 0
                AND assignment.course = kanban.course
                AND assignment.duedate > :now
           ORDER BY assignment.duedate ASC, kanban.course ASC",
            $params
        );

        $deadlines = [];
        foreach ($records as $record) {
            $courseid = (int)$record->course;
            if (!isset($deadlines[$courseid])) {
                $deadlines[$courseid] = [
                    'courseid' => $courseid,
                    'coursename' => format_string($record->coursename),
                    'assignmentname' => format_string($record->assignmentname),
                    'duedate' => (int)$record->duedate,
                    'remaining' => format_time(max(0, (int)$record->duedate - time())),
                ];
            }
        }

        return array_slice(array_values($deadlines), 0, 3);
    }

    /**
     * Render the accessible widget panel.
     *
     * @param string $widgetid Widget ID.
     * @param int $assignmentcount User's Kanban card count.
     * @param int $coursecount User's enrolled course count.
     * @param array $deadlines Upcoming deadlines.
     * @return string
     */
    private function render_panel($widgetid, $assignmentcount, $coursecount, array $deadlines) {
        $stats = html_writer::div(
            html_writer::tag('span', get_string('total_assignments', 'block_buttonkb'), ['class' => 'buttonkb-stat-label']) .
            html_writer::tag('strong', (string)$assignmentcount, ['class' => 'buttonkb-stat-value']),
            'buttonkb-stat'
        );
        $stats .= html_writer::div(
            html_writer::tag('span', get_string('joined_courses', 'block_buttonkb'), ['class' => 'buttonkb-stat-label']) .
            html_writer::tag('strong', (string)$coursecount, ['class' => 'buttonkb-stat-value']),
            'buttonkb-stat'
        );

        $items = '';
        foreach ($deadlines as $deadline) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $deadline['courseid']]);
            $name = html_writer::tag('span', $deadline['coursename'], ['class' => 'buttonkb-course-name']);
            $assignment = html_writer::tag('span', $deadline['assignmentname'], ['class' => 'buttonkb-assignment-name']);
            $remaining = html_writer::tag('span',
                get_string('time_remaining', 'block_buttonkb', $deadline['remaining']),
                ['class' => 'buttonkb-time-remaining']);
            $items .= html_writer::tag('li',
                html_writer::link($courseurl, $name . $assignment) . $remaining,
                ['class' => 'buttonkb-deadline-item']);
        }

        if ($items === '') {
            $items = html_writer::tag('li', get_string('no_upcoming_deadlines', 'block_buttonkb'), [
                'class' => 'buttonkb-empty',
            ]);
        }
        $deadlinecontent = html_writer::tag('ul', $items, ['class' => 'buttonkb-deadline-list']);

        $closebutton = html_writer::tag('button', get_string('close_widget', 'block_buttonkb'), [
            'type' => 'button',
            'class' => 'buttonkb-close',
            'aria-label' => get_string('close_widget', 'block_buttonkb'),
        ]);
        $header = html_writer::tag('div',
            html_writer::tag('h2', get_string('widget_title', 'block_buttonkb'), [
                'id' => $widgetid . '-title',
                'class' => 'buttonkb-title',
            ]) . $closebutton,
            ['class' => 'buttonkb-panel-header']
        );
        $coursebutton = html_writer::link(
            new moodle_url('/my/courses.php'),
            get_string('view_all_courses', 'block_buttonkb'),
            ['class' => 'buttonkb-courses-link']
        );

        return html_writer::tag('section',
            $header .
            html_writer::div($stats, 'buttonkb-stats') .
            html_writer::tag('h3', get_string('nearest_deadlines', 'block_buttonkb'), [
                'class' => 'buttonkb-section-title',
            ]) .
            $deadlinecontent .
            html_writer::div($coursebutton, 'buttonkb-panel-footer'),
            [
                'id' => $widgetid . '-panel',
                'class' => 'buttonkb-panel',
                'role' => 'dialog',
                'aria-modal' => 'false',
                'aria-labelledby' => $widgetid . '-title',
                'hidden' => 'hidden',
            ]
        );
    }
}
