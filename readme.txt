=== Scoped CSS ===
Contributors: sulaimandauda
Tags: css, custom css, conditional css, page-specific css, scoped styles
Requires at least: 5.3
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Load custom CSS based on page, post, or URL conditions. A lightweight conditional CSS manager.

== Description ==

Scoped CSS is a lightweight plugin that allows you to add custom CSS rules with display conditions. Unlike traditional custom CSS plugins that apply styles globally, Scoped CSS lets you target specific pages, posts, post types, or URLs.

= Features =

* **Unlimited CSS Rules** – Create as many CSS rules as you need
* **Conditional Loading** – Load CSS only where it's needed
* **Multiple Output Locations** – Choose head, footer, or body output
* **CodeMirror Editor** – Syntax highlighting and error checking
* **CSS Minify/Format** – One-click minification and formatting
* **Performance Optimized** – No frontend JavaScript, cached queries
* **Native WordPress UI** – Clean, familiar admin interface
* **Secure** – Proper capability checks and sanitization

= Display Conditions =

Each CSS rule can be targeted to:

* **Entire Site** – Apply everywhere
* **Front Page** – Homepage only
* **Blog Page** – Posts listing page
* **404 Page** – Error page
* **Specific Page** – Target individual pages
* **Specific Post** – Target individual posts
* **Post Type** – All posts of a type (pages, posts, products, etc.)
* **URL Exact Match** – Match exact URL path
* **URL Contains** – Match URLs containing a string
* **URL Starts With** – Match URLs by prefix

Conditions can be combined with AND/OR logic for complex targeting.

= Use Cases =

* Add custom styles to specific landing pages
* Override theme styles on certain post types
* Apply promotional banners only to specific URLs
* Fix layout issues on individual pages without affecting the rest of the site

== Installation ==

1. Upload the `scoped-css` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Appearance → Scoped CSS** to create your first CSS rule

== Frequently Asked Questions ==

= Does this plugin slow down my site? =

No. Scoped CSS is optimized for performance:

* Single database query to fetch all rules (cached)
* No JavaScript loaded on the frontend
* Conditions evaluated with native WordPress functions
* Rules cached using WordPress transients

= Can I migrate from Simple Custom CSS? =

Yes! When you activate Scoped CSS, any existing CSS from Simple Custom CSS will be automatically migrated to a new rule called "Migrated Global CSS" with "Entire Site" targeting.

= Can I use this alongside other CSS plugins? =

Yes. Scoped CSS outputs standard `<style>` tags and doesn't interfere with other plugins.

= Is it multisite compatible? =

Yes, each site in a multisite network can have its own CSS rules.

= How do I target multiple pages with one rule? =

Use the "URL Contains" or "URL Starts With" condition for wildcard-like matching, or create multiple conditions with "Match Any" (OR) logic.

== Screenshots ==

1. CSS Rules list with status, location, and conditions overview
2. CSS editor with CodeMirror syntax highlighting and minify/format buttons
3. Display conditions builder with multiple condition types
4. Output location selector

== Changelog ==

= 1.0.0 =
* Initial release
* Custom Post Type architecture
* Conditional CSS loading with 10 condition types
* CodeMirror editor integration
* CSS minify and format tools
* AND/OR condition logic
* Head/Footer/Body output locations
* Rate-limited AJAX endpoints
* CSS validation with dangerous pattern detection
* Automatic migration from Simple Custom CSS
* WP-CLI support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Scoped CSS – a conditional CSS manager for WordPress.
