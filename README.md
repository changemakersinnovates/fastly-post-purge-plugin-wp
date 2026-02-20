# Fastly Post Purge

Fastly Post Purge is a lightweight WordPress plugin that automatically purges Fastly cache entries when a post is saved and published.

## What it does

When a published post is saved, the plugin purges the following URLs from Fastly:

- Home page (`/`)
- News page (`/news/`)
- Events page (`/events/`)
- Calendar page (`/calendar/`)
- Employment page (`/campus-community/employment/`)
- The saved post permalink

To keep purge targets consistent, URLs are normalized to the `ukings.ca` domain before purge requests are sent.

## Requirements

- WordPress
- Fastly integration that provides the `Purgely_Purge` class

> This plugin relies on `Purgely_Purge` for cache invalidation. If that class is unavailable, purge attempts are skipped and a log message is written.

## Installation

1. Copy this plugin into your WordPress plugins directory:
   - `wp-content/plugins/fastly-post-purge-plugin-wp`
2. Activate **Fastly Post Purge** from the WordPress admin Plugins screen.
3. Ensure your Fastly/Purgely plugin is installed and active.

## How it works

- Hooks into `save_post`.
- Ignores autosaves and revisions.
- Only purges for posts with status `publish`.
- Purges each target URL via `Purgely_Purge::purge(Purgely_Purge::URL, $url)`.
- Writes success/failure details to the PHP error log.

## Notes

- The plugin is initialized in the WordPress admin context (`is_admin()`).
- Purge behavior is URL-based and does not use surrogate keys.

## License

GPL v2 or later.
