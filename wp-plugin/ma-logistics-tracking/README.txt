=== MA Logistics Tracking ===
Contributors: granthinfotech
Tags: tracking, logistics, awb, courier, elementor
Requires at least: 5.8
Tested up to: 7.0.4
Stable tag: 1.0.0
License: GPLv2 or later

High-performance, responsive shipment tracking shortcode for MA Logistics ERP with URL deep-linking and interactive milestone timeline.

== Description ==

MA Logistics Tracking embeds a lightweight tracking component into any WordPress page or Elementor template.
It communicates directly with the MA Logistics ERP public API (`https://granthinfotech.online/api/track/`), rendering consignment metadata and tracking timeline logs.

== Installation ==

1. Upload the `ma-logistics-tracking` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Place `[ma_tracking]` inside any WordPress post, page, or Elementor Shortcode widget.

== Shortcode Attributes ==

`[ma_tracking]`

Customizable attributes:
* `api_url` — Custom ERP tracking API URL (Default: `https://granthinfotech.online/api/track/`)
* `title` — Search card heading (Default: `Track Shipment`)
* `subtitle` — Search card description
* `placeholder` — Search input placeholder
* `button_text` — Submit button text (Default: `Track`)
* `primary_color` — Primary theme accent color (Default: `#2563eb`)

Example:
`[ma_tracking primary_color="#f48b24" title="Track Consignment"]`

== Deep Linking ==

Support direct customer links:
`https://yourdomain.com/track-your-order/?awb=04637824`
When visited, the page automatically populates the input and executes the search.
