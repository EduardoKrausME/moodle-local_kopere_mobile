# Kopere APP Mobile

**Kopere APP Mobile** provides the Moodle-side integration used by the dedicated mobile application, with a strong focus on reliable access in environments where connectivity is slow, intermittent or temporarily unavailable.

The plugin works through Moodle APIs and extends file delivery so the app can request content in a form suitable for offline use. This is useful in scenarios such as field work, travel, remote locations and corporate training where a stable connection cannot be assumed.

## Main features

- Mobile integration based on Moodle APIs.
- Offline-aware file delivery for supported resources.
- Support for course content such as folders, pages, files, URLs, pictures, Super Video and other integrated modules.
- Handling for external repository files when an offline copy must be served instead of a redirect.
- Reduced dependence on continuous connectivity for supported course resources.
- Progress and course information exposed to the mobile experience through Moodle services.
- Integration designed to work with the Kopere ecosystem without modifying Moodle core.

Offline availability depends on the resource or activity involved. Content that can be safely cached or downloaded can be made available without a connection, while activities that require live server state may still require internet access.

The objective is not to replace Moodle's web interface, but to provide a mobile experience that remains practical when bandwidth, latency and connectivity are real operational constraints.
