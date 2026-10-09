<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add the button widget to the site front page and user dashboards.
 *
 * @return void
 */
function xmldb_block_buttonkb_install() {
    global $DB;

    $systemcontext = context_system::instance();
    if (!$DB->record_exists('block_instances', [
            'blockname' => 'buttonkb',
            'parentcontextid' => $systemcontext->id,
            'pagetypepattern' => 'site-index',
            'subpagepattern' => null,
        ])) {
        $page = new moodle_page();
        $page->set_context($systemcontext);
        $page->blocks->add_region(BLOCK_POS_RIGHT);
        $page->blocks->add_block('buttonkb', BLOCK_POS_RIGHT, 0, false, 'site-index');
    }

    $defaultmypage = $DB->get_record('my_pages', [
        'userid' => null,
        'name' => '__default',
        'private' => 1,
    ]);
    $subpagepattern = $defaultmypage ? $defaultmypage->id : null;
    if (!$DB->record_exists('block_instances', [
            'blockname' => 'buttonkb',
            'parentcontextid' => $systemcontext->id,
            'pagetypepattern' => 'my-index',
            'subpagepattern' => $subpagepattern,
        ])) {
        $page = new moodle_page();
        $page->set_context($systemcontext);
        $page->blocks->add_region(BLOCK_POS_RIGHT);
        $page->blocks->add_block('buttonkb', BLOCK_POS_RIGHT, 0, false, 'my-index', $subpagepattern);
    }

    $sql = "SELECT MIN(id) AS id, parentcontextid, subpagepattern
              FROM {block_instances}
             WHERE pagetypepattern = :pagetype
               AND blockname <> :blockname
               AND parentcontextid <> :systemcontext
          GROUP BY parentcontextid, subpagepattern";
    $existinginstances = $DB->get_records_sql($sql, [
        'pagetype' => 'my-index',
        'blockname' => 'buttonkb',
        'systemcontext' => $systemcontext->id,
    ]);
    $now = time();

    foreach ($existinginstances as $existinginstance) {
        if ($DB->record_exists('block_instances', [
                'blockname' => 'buttonkb',
                'parentcontextid' => $existinginstance->parentcontextid,
                'pagetypepattern' => 'my-index',
                'subpagepattern' => $existinginstance->subpagepattern,
            ])) {
            continue;
        }

        $DB->insert_record('block_instances', (object)[
            'blockname' => 'buttonkb',
            'parentcontextid' => $existinginstance->parentcontextid,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'my-index',
            'subpagepattern' => $existinginstance->subpagepattern,
            'defaultregion' => BLOCK_POS_RIGHT,
            'defaultweight' => 0,
            'configdata' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
