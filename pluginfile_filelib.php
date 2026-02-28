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
 * pluginfile filelib file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function delegates file serving to individual plugins
 *
 * @param string $relativepath
 * @param bool $forcedownload
 * @param null|string $preview the preview mode, defaults to serving the original file
 * @param boolean $offline     If offline is requested - don't serve a redirect to an external file, return a file
 *                             suitable for viewing offline (e.g. mobile app).
 * @param bool $embed          Whether this file will be served embed into an iframe.
 *
 * @throws Exception
 */
function localpluginfile_file_pluginfile($relativepath, $forcedownload, $preview = null, $offline = false, $embed = false) {
    global $DB, $CFG, $USER;
    // Relative path must start with "/".
    if (!$relativepath) {
        throw new moodle_exception("invalidargorconf");
    } else if ($relativepath[0] != "/") {
        throw new moodle_exception("pathdoesnotstartslash");
    }

    // Extract relative path components.
    $args = explode("/", ltrim($relativepath, "/"));

    if (count($args) < 3) { // Always at least context, component and filearea.
        throw new moodle_exception("invalidarguments");
    }

    $contextid = (int)array_shift($args);
    $component = clean_param(array_shift($args), PARAM_COMPONENT);
    $filearea = clean_param(array_shift($args), PARAM_AREA);

    list($context, $course, $cm) = get_context_info_array($contextid);

    $fs = get_file_storage();

    $sendfileoptions = ["preview" => $preview, "offline" => $offline, "embed" => $embed];

    if ($component === "blog") {
        // Blog file serving.
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            kopere_send_file_not_found();
        }
        if ($filearea !== "attachment" && $filearea !== "post") {
            kopere_send_file_not_found();
        }

        if (empty($CFG->enableblogs)) {
            throw new moodle_exception("siteblogdisable", "blog");
        }

        $entryid = (int)array_shift($args);
        if (!$entry = $DB->get_record("post", ["module" => "blog", "id" => $entryid])) {
            kopere_send_file_not_found();
        }
        if ($CFG->bloglevel < BLOG_GLOBAL_LEVEL) {
            require_login();
            if (isguestuser()) {
                throw new moodle_exception("noguest");
            }
            if ($CFG->bloglevel == BLOG_USER_LEVEL) {
                if ($USER->id != $entry->userid) {
                    kopere_send_file_not_found();
                }
            }
        }

        if ($entry->publishstate === "public") {
            if ($CFG->forcelogin) {
                require_login();
            }

        } else if ($entry->publishstate === "site") {
            require_login();
            // Ok.
        } else if ($entry->publishstate === "draft") {
            require_login();
            if ($USER->id != $entry->userid) {
                kopere_send_file_not_found();
            }
        }

        $filename = array_pop($args);
        $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
        $file = $fs->get_file($context->id, $component, $filearea, $entryid, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            kopere_send_file_not_found();
        }

        localpluginfile_send_stored_file($file, 10 * 60, 0, true, $sendfileoptions); // Download MUST be forced - security!

    } else if ($component === "grade") {

        require_once("{$CFG->libdir}/grade/constants.php");

        if (($filearea === "outcome" || $filearea === "scale") && $context->contextlevel == CONTEXT_SYSTEM) {
            // Global gradebook files.
            if ($CFG->forcelogin) {
                require_login();
            }

            $fullpath = "/{$context->id}/{$component}/{$filearea}/" . implode("/", $args);
            $file = $fs->get_file_by_hash(sha1($fullpath));
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else if ($filearea == GRADE_FEEDBACK_FILEAREA || $filearea == GRADE_HISTORY_FEEDBACK_FILEAREA) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                kopere_send_file_not_found();
            }

            require_login($course, false);

            $gradeid = (int)array_shift($args);
            $filename = array_pop($args);
            if ($filearea == GRADE_HISTORY_FEEDBACK_FILEAREA) {
                $grade = $DB->get_record("grade_grades_history", ["id" => $gradeid]);
            } else {
                $grade = $DB->get_record("grade_grades", ["id" => $gradeid]);
            }

            if (!$grade) {
                kopere_send_file_not_found();
            }

            $iscurrentuser = $USER->id == $grade->userid;

            if (!$iscurrentuser) {
                $coursecontext = context_course::instance($course->id);
                if (!has_capability("moodle/grade:viewall", $coursecontext)) {
                    kopere_send_file_not_found();
                }
            }

            $fullpath = "/{$context->id}/{$component}/{$filearea}/{$gradeid}/{$filename}";
            $file = $fs->get_file_by_hash(sha1($fullpath));
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);
        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "tag") {
        if ($filearea === "description" && $context->contextlevel == CONTEXT_SYSTEM) {

            // All tag descriptions are going to be public but we still need to respect forcelogin.
            if ($CFG->forcelogin) {
                require_login();
            }

            $fullpath = "/{$context->id}/tag/description/" . implode("/", $args);
            $file = $fs->get_file_by_hash(sha1($fullpath));
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, true, $sendfileoptions);

        } else {
            kopere_send_file_not_found();
        }
    } else if ($component === "badges") {
        require_once("{$CFG->libdir}/badgeslib.php");

        $badgeid = (int)array_shift($args);
        $badge = new badge($badgeid);
        $filename = array_pop($args);

        if ($filearea === "badgeimage") {
            if ($filename !== "f1" && $filename !== "f2" && $filename !== "f3") {
                kopere_send_file_not_found();
            }
            if (!$file = $fs->get_file($context->id, "badges", "badgeimage", $badge->id, "/", $filename . ".png")) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close();
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);
        } else if ($filearea === "userbadge" && $context->contextlevel == CONTEXT_USER) {
            if (!$file = $fs->get_file($context->id, "badges", "userbadge", $badge->id, "/", $filename . ".png")) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close();
            localpluginfile_send_stored_file($file, 60 * 60, 0, true, $sendfileoptions);
        }
    } else if ($component === "calendar") {
        if ($filearea === "event_description" && $context->contextlevel == CONTEXT_SYSTEM) {

            // All events here are public the one requirement is that we respect forcelogin.
            if ($CFG->forcelogin) {
                require_login();
            }

            // Get the event if from the args array.
            $eventid = array_shift($args);

            // Load the event from the database.
            if (!$event = $DB->get_record("event", ["id" => (int)$eventid, "eventtype" => "site"])) {
                kopere_send_file_not_found();
            }

            // Get the file and serve if successful.
            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, $component, $filearea, $eventid, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else if ($filearea === "event_description" && $context->contextlevel == CONTEXT_USER) {

            // Must be logged in, if they are not then they obviously can't be this user.
            require_login();

            // Don't want guests here, potentially saves a DB call.
            if (isguestuser()) {
                kopere_send_file_not_found();
            }

            // Get the event if from the args array.
            $eventid = array_shift($args);

            // Load the event from the database - user id must match.
            if (!$event = $DB->get_record("event", ["id" => (int)$eventid, "userid" => $USER->id, "eventtype" => "user"])) {
                kopere_send_file_not_found();
            }

            // Get the file and serve if successful.
            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, $component, $filearea, $eventid, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 0, 0, true, $sendfileoptions);

        } else if ($filearea === "event_description" && $context->contextlevel == CONTEXT_COURSE) {

            // Respect forcelogin and require login unless this is the site.... it probably.
            // Should NEVER be the site.
            if ($CFG->forcelogin || $course->id != SITEID) {
                require_login($course);
            }

            // Must be able to at least view the course. This does not apply to the front page.
            if ($course->id != SITEID && (!is_enrolled($context)) && (!is_viewing($context))) {
                // Hmm, do we really want to block guests here?
                kopere_send_file_not_found();
            }

            // Get the event id.
            $eventid = array_shift($args);

            // Load the event from the database we need to check whether it is.
            // A) valid course event.
            // B) a group event.
            // Group events use the course context (there is no group context).
            if (!$event = $DB->get_record("event", ["id" => (int)$eventid, "courseid" => $course->id])) {
                kopere_send_file_not_found();
            }

            // If its a group event require either membership of view all groups capability.
            if ($event->eventtype === "group") {
                if (!has_capability("moodle/site:accessallgroups", $context) && !groups_is_member($event->groupid, $USER->id)) {
                    kopere_send_file_not_found();
                }
            } else if ($event->eventtype !== "course" && $event->eventtype !== "site") {
                kopere_send_file_not_found();
            }

            // If we get this far we can serve the file.
            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, $component, $filearea, $eventid, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "user") {
        if ($filearea === "icon" && $context->contextlevel == CONTEXT_USER) {
            if (count($args) == 1) {
                $themename = theme_config::DEFAULT_THEME;
                $filename = array_shift($args);
            } else {
                $themename = array_shift($args);
                $filename = array_shift($args);
            }

            // Fix file name automatically.
            if ($filename !== "f1" && $filename !== "f2" && $filename !== "f3") {
                $filename = "f1";
            }

            if ((!empty($CFG->forcelogin) && !isloggedin()) ||
                (!empty($CFG->forceloginforprofileimage) && (!isloggedin() || isguestuser()))) {
                // Protect images if login required and not logged in;
                // Also if login is required for profile images and is not logged in or guest.
                // Do not use require_login() because it is expensive and not suitable here anyway.
                $theme = theme_config::load($themename);
                redirect($theme->image_url("u/{$filename}", "moodle")); // Intentionally not cached.
            }

            if (!$file = $fs->get_file($context->id, "user", "icon", 0, "/", $filename . ".png")) {
                if (!$file = $fs->get_file($context->id, "user", "icon", 0, "/", $filename . ".jpg")) {
                    if ($filename === "f3") {
                        // F3 512x512px was introduced in 2.3, there might be only the smaller version.
                        if (!$file = $fs->get_file($context->id, "user", "icon", 0, "/", "f1.png")) {
                            $file = $fs->get_file($context->id, "user", "icon", 0, "/", "f1.jpg");
                        }
                    }
                }
            }
            if (!$file) {
                // Bad reference - try to prevent future retries as hard as possible!
                if ($user = $DB->get_record("user", ["id" => $context->instanceid], "id, picture")) {
                    if ($user->picture > 0) {
                        $DB->set_field("user", "picture", 0, ["id" => $user->id]);
                    }
                }
                // No redirect here because it is not cached.
                $theme = theme_config::load($themename);
                $imagefile = $theme->resolve_image_location("u/{$filename}", "moodle", null);
                send_file($imagefile, basename($imagefile), 60 * 60 * 24 * 14);
            }

            $options = $sendfileoptions;
            if (empty($CFG->forcelogin) && empty($CFG->forceloginforprofileimage)) {
                // Profile images should be cache-able by both browsers and proxies according.
                // To $CFG->forcelogin and $CFG->forceloginforprofileimage.
                $options["cacheability"] = "public";
            }
            // Enable long caching, there are many images on each page.
            localpluginfile_send_stored_file($file, 60 * 60 * 24 * 365, 0, false, $options);

        } else if ($filearea === "private" && $context->contextlevel == CONTEXT_USER) {
            require_login();

            if (isguestuser()) {
                kopere_send_file_not_found();
            }

            if ($USER->id !== $context->instanceid) {
                kopere_send_file_not_found();
            }

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, $component, $filearea, 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 0, 0, true, $sendfileoptions); // Must force download - security!

        } else if ($filearea === "profile" && $context->contextlevel == CONTEXT_USER) {

            if ($CFG->forcelogin) {
                require_login();
            }

            $userid = $context->instanceid;

            if ($USER->id != $userid && !empty($CFG->forceloginforprofiles)) {
                require_login();

                if (isguestuser()) {
                    kopere_send_file_not_found();
                }

                // We allow access to site profile of all course contacts (usually teachers).
                if (!has_coursecontact_role($userid) && !has_capability("moodle/user:viewdetails", $context)) {
                    kopere_send_file_not_found();
                }

                $canview = false;
                if (has_capability("moodle/user:viewdetails", $context)) {
                    $canview = true;
                } else {
                    $courses = enrol_get_my_courses();
                }

                while (!$canview && count($courses) > 0) {
                    $course = array_shift($courses);
                    if (has_capability("moodle/user:viewdetails", context_course::instance($course->id))) {
                        $canview = true;
                    }
                }
            }

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, $component, $filearea, 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 0, 0, true, $sendfileoptions); // Must force download - security!

        } else if ($filearea === "profile" && $context->contextlevel == CONTEXT_COURSE) {
            $userid = (int)array_shift($args);
            $usercontext = context_user::instance($userid);

            if ($CFG->forcelogin) {
                require_login();
            }

            if (!empty($CFG->forceloginforprofiles)) {
                require_login();
                if (isguestuser()) {
                    throw new moodle_exception("noguest");
                }

                // Review this logic of user profile access prevention.
                if (!has_coursecontact_role($userid) && !has_capability("moodle/user:viewdetails", $usercontext)) {
                    throw new moodle_exception("usernotavailable");
                }
                if (!has_capability("moodle/user:viewdetails", $context) &&
                    !has_capability("moodle/user:viewdetails", $usercontext)) {
                    throw new moodle_exception("cannotviewprofile");
                }
                if (!is_enrolled($context, $userid)) {
                    throw new moodle_exception("notenrolledprofile");
                }
                if (groups_get_course_groupmode($course) == SEPARATEGROUPS &&
                    !has_capability("moodle/site:accessallgroups", $context)) {
                    throw new moodle_exception("groupnotamember");
                }
            }

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($usercontext->id, "user", "profile", 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 0, 0, true, $sendfileoptions); // Must force download - security!

        } else if ($filearea === "backup" && $context->contextlevel == CONTEXT_USER) {
            require_login();

            if (isguestuser()) {
                kopere_send_file_not_found();
            }
            $userid = $context->instanceid;

            if ($USER->id != $userid) {
                kopere_send_file_not_found();
            }

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "user", "backup", 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 0, 0, true, $sendfileoptions); // Must force download - security!

        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "coursecat") {
        if ($context->contextlevel != CONTEXT_COURSECAT) {
            kopere_send_file_not_found();
        }

        if ($filearea === "description") {
            if ($CFG->forcelogin) {
                // No login necessary - unless login forced everywhere.
                require_login();
            }

            // Check if user can view this category.
            if (!core_course_category::get($context->instanceid, IGNORE_MISSING)) {
                kopere_send_file_not_found();
            }

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "coursecat", "description", 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);
        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "course") {
        if ($context->contextlevel != CONTEXT_COURSE) {
            kopere_send_file_not_found();
        }

        if ($filearea === "summary" || $filearea === "overviewfiles") {
            if ($CFG->forcelogin) {
                require_login();
            }

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "course", $filearea, 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else if ($filearea === "section") {
            if ($CFG->forcelogin) {
                require_login($course);
            } else if ($course->id != SITEID) {
                require_login($course);
            }

            $sectionid = (int)array_shift($args);

            if (!$section = $DB->get_record("course_sections", ["id" => $sectionid, "course" => $course->id])) {
                kopere_send_file_not_found();
            }

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "course", "section", $sectionid, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "cohort") {

        $cohortid = (int)array_shift($args);
        $cohort = $DB->get_record("cohort", ["id" => $cohortid], "*", MUST_EXIST);
        $cohortcontext = context::instance_by_id($cohort->contextid);

        // The context in the file URL must be either cohort context or context of the course underneath the cohort"s context.
        if ($context->id != $cohort->contextid &&
            ($context->contextlevel != CONTEXT_COURSE || !in_array($cohort->contextid, $context->get_parent_context_ids()))) {
            kopere_send_file_not_found();
        }

        // User is able to access cohort if they have view cap on cohort level or.
        // The cohort is visible and they have view cap on course level.
        $canview = has_capability("moodle/cohort:view", $cohortcontext) ||
            ($cohort->visible && has_capability("moodle/cohort:view", $context));

        if ($filearea === "description" && $canview) {
            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            if (($file = $fs->get_file($cohortcontext->id, "cohort", "description", $cohort->id, $filepath, $filename))
                && !$file->is_directory()) {
                \core\session\manager::write_close(); // Unlock session during file serving.
                localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);
            }
        }

        kopere_send_file_not_found();
    } else if ($component === "group") {
        if ($context->contextlevel != CONTEXT_COURSE) {
            kopere_send_file_not_found();
        }

        require_course_login($course, true, null, false);

        $groupid = (int)array_shift($args);

        $group = $DB->get_record("groups", ["id" => $groupid, "courseid" => $course->id], "*", MUST_EXIST);
        if (($course->groupmodeforce && $course->groupmode == SEPARATEGROUPS) &&
            !has_capability("moodle/site:accessallgroups", $context) && !groups_is_member($group->id, $USER->id)) {
            // Do not allow access to separate group info if not member or teacher.
            kopere_send_file_not_found();
        }

        if ($filearea === "description") {

            require_login($course);

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "group", "description", $group->id, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else if ($filearea === "icon") {
            $filename = array_pop($args);

            if ($filename !== "f1" && $filename !== "f2") {
                kopere_send_file_not_found();
            }
            if (!$file = $fs->get_file($context->id, "group", "icon", $group->id, "/", $filename . ".png")) {
                if (!$file = $fs->get_file($context->id, "group", "icon", $group->id, "/", $filename . ".jpg")) {
                    kopere_send_file_not_found();
                }
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, false, $sendfileoptions);

        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "grouping") {
        if ($context->contextlevel != CONTEXT_COURSE) {
            kopere_send_file_not_found();
        }

        require_login($course);

        $groupingid = (int)array_shift($args);

        // Note: everybody has access to grouping desc images for now.
        if ($filearea === "description") {

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "grouping", "description", $groupingid, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "backup") {
        if ($filearea === "course" && $context->contextlevel == CONTEXT_COURSE) {
            require_login($course);
            require_capability("moodle/backup:downloadfile", $context);

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "backup", "course", 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 0, 0, $forcedownload, $sendfileoptions);

        } else if ($filearea === "section" && $context->contextlevel == CONTEXT_COURSE) {
            require_login($course);
            require_capability("moodle/backup:downloadfile", $context);

            $sectionid = (int)array_shift($args);

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "backup", "section", $sectionid, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close();
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else if ($filearea === "activity" && $context->contextlevel == CONTEXT_MODULE) {
            require_login($course, false, $cm);
            require_capability("moodle/backup:downloadfile", $context);

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "backup", "activity", 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close();
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);

        } else if ($filearea === "automated" && $context->contextlevel == CONTEXT_COURSE) {
            // Backup files that were generated by the automated backup systems.

            require_login($course);
            require_capability("moodle/backup:downloadfile", $context);
            require_capability("moodle/restore:userinfo", $context);

            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "backup", "automated", 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 0, 0, $forcedownload, $sendfileoptions);

        } else {
            kopere_send_file_not_found();
        }

    } else if ($component === "question") {
        require_once("{$CFG->libdir}/questionlib.php");
        $sendfileoptions["preview"] = null;
        question_pluginfile($course, $context, "question", $filearea, $args, $forcedownload, $sendfileoptions);
        kopere_send_file_not_found();

    } else if ($component === "grading") {
        if ($filearea === "description") {
            // Files embedded into the form definition description.

            if ($context->contextlevel == CONTEXT_SYSTEM) {
                require_login();

            } else if ($context->contextlevel >= CONTEXT_COURSE) {
                require_login($course, false, $cm);

            } else {
                kopere_send_file_not_found();
            }

            $formid = (int)array_shift($args);

            $sql = "SELECT ga.id
                FROM {grading_areas} ga
                JOIN {grading_definitions} gd ON (gd.areaid = ga.id)
                WHERE gd.id = ? AND ga.contextid = ?";
            $areaid = $DB->get_field_sql($sql, [$formid, $context->id], IGNORE_MISSING);

            if (!$areaid) {
                kopere_send_file_not_found();
            }

            $fullpath = "/{$context->id}/{$component}/{$filearea}/{$formid}/" . implode("/", $args);
            $file = $fs->get_file_by_hash(sha1($fullpath));
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            localpluginfile_send_stored_file($file, 60 * 60, 0, $forcedownload, $sendfileoptions);
        }

    } else if (strpos($component, "mod_") === 0) {
        $modname = substr($component, 4);
        if (!file_exists("{$CFG->dirroot}/mod/{$modname}/lib.php")) {
            kopere_send_file_not_found();
        }
        require_once("{$CFG->dirroot}/mod/{$modname}/lib.php");

        if ($context->contextlevel == CONTEXT_MODULE) {
            if ($cm->modname !== $modname) {
                // Somebody tries to gain illegal access, cm type must match the component!
                kopere_send_file_not_found();
            }
        }

        if ($filearea === "intro") {
            if (!plugin_supports("mod", $modname, FEATURE_MOD_INTRO, true)) {
                kopere_send_file_not_found();
            }

            // Require login to the course first (without login to the module).
            require_course_login($course, true);

            // Now check if module is available OR it is restricted but the intro is shown on the course page.
            $cminfo = cm_info::create($cm);
            if (!$cminfo->uservisible) {
                if (!$cm->showdescription || !$cminfo->is_visible_on_course_page()) {
                    // Module intro is not visible on the course page and module is not available, show access error.
                    require_course_login($course, true, $cminfo);
                }
            }

            // All users may access it.
            $filename = array_pop($args);
            $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
            $file = $fs->get_file($context->id, "mod_" . $modname, "intro", 0, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                kopere_send_file_not_found();
            }

            // Finally send the file.
            localpluginfile_send_stored_file($file, null, 0, false, $sendfileoptions);
        }

        $filefunction = $component . "_pluginfile";
        $filefunctionold = $modname . "_pluginfile";
        if (function_exists($filefunction)) {
            // If the function exists, it must send the file and terminate. Whatever it returns leads to "not found".
            $filefunction($course, $cm, $context, $filearea, $args, $forcedownload, $sendfileoptions);
        } else if (function_exists($filefunctionold)) {
            // If the function exists, it must send the file and terminate. Whatever it returns leads to "not found".
            $filefunctionold($course, $cm, $context, $filearea, $args, $forcedownload, $sendfileoptions);
        }

        kopere_send_file_not_found();

    } else if (strpos($component, "block_") === 0) {
        $blockname = substr($component, 6);
        // Note: no more class methods in blocks please, that is ...
        if (!file_exists("{$CFG->dirroot}/blocks/{$blockname}/lib.php")) {
            kopere_send_file_not_found();
        }
        require_once("{$CFG->dirroot}/blocks/{$blockname}/lib.php");

        if ($context->contextlevel == CONTEXT_BLOCK) {
            $birecord = $DB->get_record("block_instances", ["id" => $context->instanceid], "*", MUST_EXIST);
            if ($birecord->blockname !== $blockname) {
                // Somebody tries to gain illegal access, cm type must match the component!
                kopere_send_file_not_found();
            }

            if ($context->get_course_context(false)) {
                // If block is in course context, then check if user has capability to access course.
                require_course_login($course);
            } else if ($CFG->forcelogin) {
                // If user is logged out, bp record will not be visible, even if the user would have access if logged in.
                require_login();
            }

            $bprecord = $DB->get_record("block_positions",
                ["contextid" => $context->id, "blockinstanceid" => $context->instanceid]);
            // User can't access file, if block is hidden or doesn't have block:view capability.
            if (($bprecord && !$bprecord->visible) || !has_capability("moodle/block:view", $context)) {
                kopere_send_file_not_found();
            }
        } else {
            $birecord = null;
        }

        $filefunction = $component . "_pluginfile";
        if (function_exists($filefunction)) {
            // If the function exists, it must send the file and terminate. Whatever it returns leads to "not found".
            $filefunction($course, $birecord, $context, $filearea, $args, $forcedownload, $sendfileoptions);
        }

        kopere_send_file_not_found();

    } else if (strpos($component, "_") === false) {
        // All core subsystems have to be specified above, no more guessing here!
        kopere_send_file_not_found();

    } else {
        // Try to serve general plugin file in arbitrary context.
        $dir = core_component::get_component_directory($component);
        if (!file_exists("{$dir}/lib.php")) {
            kopere_send_file_not_found();
        }
        include_once("{$dir}/lib.php");

        $filefunction = $component . "_pluginfile";
        if (function_exists($filefunction)) {
            // If the function exists, it must send the file and terminate. Whatever it returns leads to "not found".
            $filefunction($course, $cm, $context, $filearea, $args, $forcedownload, $sendfileoptions);
        }

        kopere_send_file_not_found();
    }
}

/**
 * Handles the sending of file data to the user's browser, including support for
 * byteranges etc.
 *
 * The $options parameter supports the following keys:
 *  (string|null) preview - send the preview of the file (e.g. "thumb" for a thumbnail)
 *  (string|null) filename - overrides the implicit filename
 *  (bool) dontdie - return control to caller afterwards. this is not recommended and only used for cleanup tasks.
 *      if this is passed as true, ignore_user_abort is called.  if you don't want your processing to continue on
 *      cancel, you must detect this case when control is returned using connection_aborted. Please not that session is
 *      closed and should not be reopened
 *  (string|null) cacheability - force the cacheability setting of the HTTP response, "private" or "public",
 *      when $lifetime is greater than 0. Cacheability defaults to "private" when logged in as other than guest;
 *      otherwise, defaults to "public".
 *  (string|null) immutable - set the immutable cache setting in the HTTP response, when served under HTTPS.
 *      Note: it's up to the consumer to set it properly i.e. when serving a "versioned" URL.
 *
 * @category files
 *
 * @param stored_file $storedfile    local file object
 * @param int $lifetime              Number of seconds before the file should expire from caches (null means
 *                                   $CFG->filelifetime)
 * @param int $filter                0 (default)=no filtering, 1=all files, 2=html files only
 * @param bool $forcedownload        If true (default false), forces download of file rather than view in
 *                                   browser/plugin
 * @param array $options             additional options affecting the file serving
 *
 * @return null script execution stopped unless $options["dontdie"] is true
 *
 * @throws Exception
 */
function localpluginfile_send_stored_file($storedfile, $lifetime = null, $filter = 0,
                                          $forcedownload = false, array $options = []) {
    global $CFG;

    if (empty($options["filename"])) {
        $filename = null;
    } else {
        $filename = $options["filename"];
    }

    if (empty($options["dontdie"])) {
        $dontdie = false;
    } else {
        $dontdie = true;
    }

    if ($lifetime === "default" || is_null($lifetime)) {
        $lifetime = $CFG->filelifetime;
    }

    if (!empty($options["preview"])) {
        // Replace the file with its preview.
        $fs = get_file_storage();
        $previewfile = localpluginfile_get_file_preview($fs, $storedfile, $options["preview"]);
        if (!$previewfile) {
            // Unable to create a preview of the file, send its default mime icon instead.
            if ($options["preview"] === "tinyicon") {
                $size = 24;
            } else if ($options["preview"] === "thumb") {
                $size = 90;
            } else {
                $size = 256;
            }
            $fileicon = file_file_icon($storedfile, $size);
            send_file("{$CFG->dirroot}/pix/{$fileicon}.png", basename($fileicon) . ".png");
        } else {
            // Preview images have fixed cache lifetime and they ignore forced download.
            // (they are generated by GD and therefore they are considered reasonably safe).
            $storedfile = $previewfile;
            $lifetime = DAYSECS;
            $filter = 0;
            $forcedownload = false;
        }
    }

    // Handle external resource.
    if ($storedfile && $storedfile->is_external_file() && !isset($options["sendcachedexternalfile"])) {
        $storedfile->send_file($lifetime, $filter, $forcedownload, $options);
        die;
    }

    if (!$storedfile || $storedfile->is_directory()) {
        // Nothing to serve.
        if ($dontdie) {
            return;
        }
        die;
    }

    $filename = is_null($filename) ? $storedfile->get_filename() : $filename;

    // Use given MIME type if specified.
    $mimetype = $storedfile->get_mimetype();

    // Allow cross-origin requests only for Web Services.
    // This allow to receive requests done by Web Workers or webapps in different domains.
    if (WS_SERVER) {
        header("Access-Control-Allow-Origin: *");
    }

    send_file($storedfile, $filename, $lifetime, $filter, false, $forcedownload, $mimetype, $dontdie, $options);
}

/**
 * Function localpluginfile_get_file_preview
 *
 * @param file_storage $fs
 * @param stored_file $file
 * @param $thumbwidth
 *
 * @return bool|stored_file
 * @throws dml_exception
 */
function localpluginfile_get_file_preview(file_storage $fs, stored_file $file, $thumbwidth) {

    $context = context_system::instance();
    $filepath = "/" . trim($thumbwidth, "/") . "/";
    $filename = $file->get_contenthash();

    $preview = $fs->get_file($context->id, "core", "preview", 0, $filepath, $filename);
    if (!$preview) {
        $preview = localpluginfile_create_file_preview($fs, $file, $thumbwidth);
        if (!$preview) {
            return false;
        }
    }

    return $preview;
}

/**
 * Function localpluginfile_create_file_preview
 *
 * @param file_storage $fs
 * @param stored_file $file
 * @param $thumbwidth
 *
 * @return bool|stored_file
 * @throws dml_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function localpluginfile_create_file_preview(file_storage $fs, stored_file $file, $thumbwidth) {

    $mimetype = $file->get_mimetype();

    if ($mimetype === "image/gif" || $mimetype === "image/jpeg" || $mimetype === "image/png") {
        // Make a preview of the image.
        $data = create_imagefile_preview($file);

    } else {
        // Unable to create the preview of this mimetype yet.
        return false;
    }

    if (empty($data)) {
        return false;
    }

    $context = context_system::instance();
    $record = [
        "contextid" => $context->id,
        "component" => "core",
        "filearea" => "preview",
        "itemid" => 0,
        "filepath" => "/" . trim($thumbwidth, "/") . "/",
        "filename" => $file->get_contenthash(),
    ];

    $imageinfo = getimagesizefromstring($data);
    if ($imageinfo) {
        $record["mimetype"] = $imageinfo["mime"];
    }

    return $fs->create_file_from_string($record, $data);
}

/**
 * Function create_imagefile_preview
 *
 * @param stored_file $file
 *
 * @return bool|string
 * @throws coding_exception
 */
function create_imagefile_preview(stored_file $file) {
    global $CFG;
    require_once("{$CFG->libdir}/gdlib.php");

    $thumbwidth = optional_param("preview", 250, PARAM_INT);
    if (empty($thumbwidth)) {
        return false;
    }

    $content = $file->get_content();

    // Fetch the image information for this image.
    $imageinfo = @getimagesizefromstring($content);
    if (empty($imageinfo)) {
        return $content;
    }

    $originalwidth = $imageinfo[0];
    if ($originalwidth < $thumbwidth) {
        return $content;
    }

    // Create a new image from the file.
    $original = @imagecreatefromstring($content);

    // Generate the thumbnail.
    return resize_image_from_image($original, $imageinfo, $thumbwidth, null);
}

/**
 * Kopere File
 */
function kopere_send_file_not_found() {
    // Allow cross-origin requests only for Web Services.
    // This allow to receive requests done by Web Workers or webapps in different domains.
    if (WS_SERVER) {
        header("Access-Control-Allow-Origin: *");
    }

    header("HTTP/1.0 404 not found");
    die;
}
