<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_buttonkb';
$plugin->version = 2026101002;
$plugin->requires = 2024100100;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.2';
$plugin->dependencies = [
    'mod_kanban' => 2026091700,
];
