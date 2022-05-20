<?php
namespace block_mad2api\task;
defined('MOODLE_INTERNAL') || die();
/**
 * An example of a scheduled task.
 */
class madLogger extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return 'MAD Logging';
    }

    /**
     * Execute the task.
     */
    public function execute() {
        \block_mad2api\mad_dashboard::scheduled_log();
    }
}
