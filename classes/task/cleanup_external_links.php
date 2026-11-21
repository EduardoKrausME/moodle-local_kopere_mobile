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
 * Scheduled task for expiring mobile browser tickets.
 *
 * @package    local_kopere_mobile
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\task;

/**
 * Scheduled cleanup of expired, unredeemed browser navigation tickets.
 *
 * @package local_kopere_mobile
 */
final class cleanup_external_links extends \core\task\scheduled_task {
    /**
     * Return the readable task name.
     *
     * @return string Task name.
     */
    public function get_name(): string {
        return 'Clean up expired mobile browser redirect tickets';
    }

    /**
     * Delete all expired browser navigation tickets.
     *
     * @return void
     */
    public function execute(): void {
        \local_kopere_mobile\external_link::purge_expired();
    }
}
