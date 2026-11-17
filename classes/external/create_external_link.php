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

namespace local_kopere_mobile\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_system;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_kopere_mobile\external_link;

/** Mint a one-minute, single-use JWT for browser navigation. */
final class create_external_link extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'url' => new external_value(PARAM_RAW, 'Validated HTTPS target URL'),
        ]);
    }

    public static function execute(string $url): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['url' => $url]);
        self::validate_context(context_system::instance());
        if (empty($USER->id) || isguestuser() || !empty($USER->deleted) || !empty($USER->suspended)) {
            throw new \moodle_exception('nopermissions', 'error');
        }
        return external_link::issue($params['url'], (int) $USER->id);
    }

    public static function execute_returns() {
        return new external_single_structure([
            'jwt' => new external_value(PARAM_RAW, 'One-use HS256 JWT, valid for 60 seconds'),
            'expiresat' => new external_value(PARAM_INT, 'Expiration Unix timestamp'),
        ]);
    }
}
