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
 * Public brand information for the Moodle Mobile app and its build panel.
 *
 * @package local_kopere_mobile
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile;

/**
 * Read branding from the active LearnLingo theme, if available.
 */
final class branding {
    /**
     * Returns only public branding data.
     *
     * @return array Public branding; no authentication tokens or private options.
     */
    public static function export(): array {
        global $CFG;

        if (($CFG->theme ?? '') === 'learnlingo'
                && class_exists('\\theme_learnlingo\\mobile_branding')) {
            return \theme_learnlingo\mobile_branding::export();
        }

        return [
            'schema_version' => 1,
            'source' => 'moodle',
            'brandcolor' => '#08422A',
            'accentcolor' => '#08422A',
            'canvascolor' => '#FFFFFF',
            'textcolor' => '#263238',
            'logo_url' => '',
            'navbar_scss' => '',
        ];
    }
}
