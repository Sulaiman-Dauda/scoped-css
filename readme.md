# Scoped CSS

[![WordPress](https://img.shields.io/badge/WordPress-5.3%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Load custom CSS based on page, post, or URL conditions. A lightweight conditional CSS manager for WordPress.

## Description

Scoped CSS allows you to add custom CSS rules with display conditions. Unlike traditional custom CSS plugins that apply styles globally, Scoped CSS lets you target specific pages, posts, post types, or URLs.

### Features

- **Unlimited CSS Rules** – Create as many CSS rules as you need
- **Conditional Loading** – Load CSS only where it's needed
- **Multiple Output Locations** – Choose head, footer, or body output
- **CodeMirror Editor** – Syntax highlighting and error checking
- **CSS Minify/Format** – One-click minification and formatting
- **Performance Optimized** – No frontend JavaScript, cached queries
- **Native WordPress UI** – Clean, familiar admin interface
- **Secure** – Proper capability checks and sanitization
- **WP-CLI Support** – Manage rules from the command line

### Display Conditions

Each CSS rule can be targeted to:

| Condition | Description |
|-----------|-------------|
| Entire Site | Apply everywhere |
| Front Page | Homepage only |
| Blog Page | Posts listing page |
| 404 Page | Error page |
| Specific Page | Target individual pages |
| Specific Post | Target individual posts |
| Post Type | All posts of a type |
| URL Exact Match | Match exact URL path |
| URL Contains | Match URLs containing a string |
| URL Starts With | Match URLs by prefix |

Conditions can be combined with **AND/OR** logic for complex targeting.

## Installation

1. Upload the `scoped-css` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Appearance → Scoped CSS** to create your first CSS rule

## Usage

1. Go to **Appearance → Scoped CSS**
2. Click **Add New** to create a CSS rule
3. Enter a title (for admin reference)
4. Add your CSS code in the editor
5. Choose where to output the CSS (Head, Footer, or Body)
6. Set display conditions to control where the CSS loads
7. Click **Publish** to activate the rule

### Tips

- Use the **Status** toggle in the rules list to quickly enable/disable rules
- Click **Minify** to compress CSS or **Format** to make it readable
- Combine multiple conditions for precise targeting
- Use "URL Contains" for wildcard-like matching

## WP-CLI Commands

```bash
# List all CSS rules
wp scoped-css list

# Enable/disable a rule
wp scoped-css enable <id>
wp scoped-css disable <id>

# Show rule details
wp scoped-css show <id>

# Clear cache
wp scoped-css cache-clear
```

## Requirements

- WordPress 5.3 or higher
- PHP 7.2 or higher

## Frequently Asked Questions

### Does this plugin slow down my site?

No. Scoped CSS is optimized for performance:
- Single database query to fetch all rules (cached)
- No JavaScript loaded on the frontend
- Conditions evaluated with native WordPress functions
- Rules cached using WordPress transients

### Can I migrate from Simple Custom CSS?

Yes! When you activate Scoped CSS, any existing CSS from Simple Custom CSS will be automatically migrated.

## Changelog

### 1.0.0
- Initial release
- Custom Post Type architecture
- Conditional CSS loading with 10 condition types
- CodeMirror editor integration
- CSS minify and format tools
- AND/OR condition logic
- Head/Footer/Body output locations
- WP-CLI support
- Automatic migration from Simple Custom CSS

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

**Sulaiman Dauda** - [GitHub](https://github.com/Sulaiman-Dauda)
