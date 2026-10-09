<?php
/**
 * Privacy provider for block_buttonkb.
 *
 * @package    block_buttonkb
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_buttonkb\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * The block does not persist any personal data.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Return the explanation that no personal data is stored.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
