# YAES
YAES (Yet Another Email Script) is a no-dependency PHP and vanilla JavaScript contact form you can drop into any mail-enabled host.

## What changed recently
- Modern UI with accessible labels, textarea, and live status messaging
- Fetch-based JSON submission (no page reloads) with graceful error handling
- UTF-8 mail headers and stricter validation on the server
- Accepts both JSON and `application/x-www-form-urlencoded` bodies for flexibility

## Dependencies
- PHP 7.4+ recommended (works on PHP 5+, but JSON handling and charset defaults are better on newer versions)
- A mail-capable host (uses `mail()` under the hood)
- Modern browsers for the frontend (Fetch + HTML5 validation)

## Configure
1) Open [email.php](email.php) and set `$to`, `$subject`, and optional `$fromOverride` if you want to force the sender address.
2) Deploy `email.php` and `email.html` to the same directory on a server with mail support.
3) Open [email.html](email.html) in the browser or embed the form markup into your site.

## Endpoint behavior
- Method: `POST` only (returns 405 otherwise)
- Body: JSON `{ "from": "you@example.com", "message": "Hello" }` or form-encoded `from` and `message`
- Responses: `201` with `{ "status": "sent" }` on success, `400` with `{ "errors": [...] }` on validation issues, `500` on mail failures

## Usage
You are free to modify and reuse this script. If you find bugs, please report them.
