=== MA Logistics Tracking ===
Contributors: marlexpress
Tags: tracking, logistics, awb, courier, elementor
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

High-performance, mobile-responsive live shipment tracking component for MARL Express & MA Logistics ERP with URL deep-linking and interactive milestone timeline.

== Description ==

MA Logistics Tracking is an enterprise tracking plugin built for MARL Express (https://marlexpress.com/).
It communicates with the MA Logistics ERP public API (`https://granthinfotech.online/api/track/`), rendering consignment metadata and tracking timeline logs.

Features:
* Zero Dependencies: Pure Vanilla JavaScript and scoped modern CSS.
* URL Parameter Auto-Tracking: Direct deep links (`https://marlexpress.com/track-your-order/?awb=04637824`) automatically initiate searches.
* One-Click Shareable Links: Clipboard copy button for instant sharing via WhatsApp or SMS.
* Dual View: Consignment details summary table alongside an interactive vertical milestone timeline.
* Performance-Engineered: Scripts and styles only load on pages where `[ma_tracking]` is rendered.

== Shortcode Usage ==

Standard usage:
`[ma_tracking]`

Customizable attributes:
* `site_url` — Your website URL (Default: `https://marlexpress.com/`)
* `api_url` — Custom ERP tracking API URL (Default: `https://granthinfotech.online/api/track/`)
* `title` — Form heading (Default: `Track Shipment`)
* `subtitle` — Form subheading
* `placeholder` — Search input placeholder (Default: `e.g. 04637824 or DCK-10383`)
* `button_text` — Submit button label (Default: `Track`)
* `primary_color` — Custom brand accent color (e.g. `#f48b24`)
