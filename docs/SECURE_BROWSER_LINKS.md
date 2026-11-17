# One-minute browser navigation (local_kopere_mobile)

This replaces a browser URL carrying a permanent Moodle authentication token
with a signed, single-use JWT issued by Moodle's authenticated web service.

## Contract

1. The app sends a standard Moodle REST POST to
   local_kopere_mobile_create_external_link, with the HTTPS destination in
   the POST body and Moodle's webservice token in the POST body only.
2. The server validates the URL, rejects credentials and credential-like
   query parameters, then stores its destination, user, expiry and SHA-256 of
   a random ticket ID in local_kopere_mobile_links.
3. The response contains jwt and expiresat. The HS256-signed JWT carries
   issuer, audience, subject, JTI, iat, nbf and exp, but no target URL
   or permanent Moodle token.
4. The browser opens only the Moodle redirect.php endpoint with ?jwt=...
5. The endpoint validates JWT and expiry, consumes it once under a Moodle
   shared lock, then sends a 303 redirect with no-store and no-referrer.
6. For URLs within the Moodle installation, the endpoint establishes the
   user's Moodle browser session. For external URLs it does not establish
   or forward a Moodle session.

JWT lifetime is 60 seconds. Consumed and expired tickets are rejected.
An hourly scheduled Moodle task purges unused expired tickets.

## Upgrade

Upgrade local_kopere_mobile from 2026111400 to 2026111700 BEFORE releasing
the mobile app. The upgrade creates the ticket table and registers the
new authenticated function in the official Moodle mobile service.

The old index.php?action=loadpage endpoint returns HTTP 410, because the
old code exposed permanent tokens and could redirect despite authentication
failure. Older application versions must be upgraded.

HTTPS is required. For multi-node sites, use a shared Moodle lock factory.
Exclude JWT-bearing redirect URLs from reverse-proxy/application logs and
analytics. Never expose external_link_secret in public settings.

Legacy pluginfile.php and offline file token transports are not changed by
this browser navigation feature.

## Tests

The Moodle PHPUnit suite covers signatures, replay prevention, expiration,
URL validation, guest access and account isolation. The GitHub workflow
validates PHP/XML syntax, Moodle XMLDB/savepoints and executes the plugin's
PHPUnit suite. End-to-end browser/cookie behavior needs live Moodle testing.
