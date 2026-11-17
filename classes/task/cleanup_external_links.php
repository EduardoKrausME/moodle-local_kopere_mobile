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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

namespace local_kopere_mobile\task;

defined('MOODLE_INTERNAL') || die();

/** Scheduled cleanup of expired, unredeemed browser navigation tickets. */
final class cleanup_external_links extends \core\task\scheduled_task {
    public function get_name(): string {
        return 'Clean up expired mobile browser redirect tickets';
    }

    public function execute(): void {
        \local_kopere_mobile\external_link::purge_expired();
    }
}
