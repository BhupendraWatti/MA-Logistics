<?php
/**
 * Plugin Name: MA Logistics Tracking
 * Plugin URI:  https://marlexpress.com/
 * Description: High-performance, mobile-responsive live shipment tracking component for MARL Express & MA Logistics ERP with URL deep-linking and interactive milestone timeline.
 * Version:     1.0.5
 * Author:      MARL Express
 * Author URI:  https://marlexpress.com/
 * License:     GPL-2.0+
 * Text Domain: ma-tracking
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('MA_TRACKING_VERSION', '1.0.5');
define('MA_TRACKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MA_TRACKING_PLUGIN_URL', plugin_dir_url(__FILE__));

class MALogisticsTracking {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_shortcode('ma_tracking', [$this, 'render_shortcode']);
    }

    /**
     * Register scripts and stylesheets (loaded only when shortcode executes or on tracking page).
     */
    public function register_assets() {
        wp_register_style(
            'ma-tracking-css',
            MA_TRACKING_PLUGIN_URL . 'assets/css/ma-tracking.css',
            [],
            MA_TRACKING_VERSION
        );

        wp_register_script(
            'ma-tracking-js',
            MA_TRACKING_PLUGIN_URL . 'assets/js/ma-tracking.js',
            [],
            MA_TRACKING_VERSION,
            true // Load in footer
        );
    }

    /**
     * Render Tracking Component Shortcode: [ma_tracking]
     *
     * Supported attributes:
     * - api_url: ERP API endpoint (default: https://granthinfotech.online/api/track/)
     * - site_url: Site URL (default: current site URL https://marlexpress.com/)
     * - title: Form title (default: Track Shipment)
     * - subtitle: Form subtitle
     * - placeholder: Input placeholder
     * - button_text: Button text
     * - primary_color: Accent color (default: #2563eb)
     */
    public function render_shortcode($atts = []) {
        $atts = shortcode_atts([
            'api_url'       => 'https://granthinfotech.online/api/track/',
            'site_url'      => home_url('/'),
            'title'         => 'Track Shipment',
            'subtitle'      => 'Enter your AWB No. or Docket No. to view real-time package logs.',
            'placeholder'   => 'e.g. 04637824 or DCK-10383',
            'button_text'   => 'Track',
            'primary_color' => '#2563eb',
        ], $atts, 'ma_tracking');

        // Enqueue registered assets safely
        wp_enqueue_style('ma-tracking-css');
        wp_enqueue_script('ma-tracking-js');

        // Pass dynamic config to JavaScript
        wp_localize_script('ma-tracking-js', 'maTrackingConfig', [
            'apiUrl'  => trailingslashit(esc_url_raw($atts['api_url'])),
            'siteUrl' => trailingslashit(esc_url_raw($atts['site_url'])),
        ]);

        $custom_style = '';
        if ($atts['primary_color'] !== '#2563eb') {
            $custom_style = sprintf(' style="--ma-primary: %s;"', esc_attr($atts['primary_color']));
        }

        ob_start();
        ?>
        <!-- ================== MA LOGISTICS CMS TRACKING COMPONENT ================== -->
        <div class="ma-tracking-container"<?php echo $custom_style; ?> id="ma-tracking-root">
            
            <!-- Search & Control Panel Wrapper -->
            <div class="ma-search-wrapper">
                <div class="ma-search-left">
                    <h2 class="ma-search-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="ma-search-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                </div>
                
                <div class="ma-search-right">
                    <form id="ma-tracking-form" class="ma-search-form" onsubmit="event.preventDefault(); initiateTrack();">
                        <div class="ma-input-group">
                            <span class="ma-input-icon" aria-hidden="true">&#128269;</span>
                            <input 
                                type="text" 
                                id="ma-awb-input" 
                                class="ma-tracking-input" 
                                placeholder="<?php echo esc_attr($atts['placeholder']); ?>" 
                                required 
                                autocomplete="off"
                                spellcheck="false"
                            >
                            <button type="submit" class="ma-tracking-btn" id="ma-track-submit-btn">
                                <span><?php echo esc_html($atts['button_text']); ?></span>
                                <div class="ma-loader" id="ma-btn-loader" aria-hidden="true"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Error Notification Alert Box -->
            <div id="ma-error-message" class="ma-error-container" style="display: none;" role="alert"></div>

            <!-- ================== DYNAMIC RESULT WRAPPER ================== -->
            <div id="ma-tracking-result-box" class="ma-result-wrapper" style="display: none;">
                
                <!-- Header Band with Status Badge & Share Button -->
                <div class="ma-result-header-band">
                    <div class="ma-header-awb-group">
                        <span class="ma-result-awb-title" id="val-awb-header">AWB: -</span>
                        <button type="button" class="ma-copy-btn" id="ma-share-link-btn" title="Copy tracking link to clipboard" onclick="copyTrackingLink();">
                            <span class="ma-copy-icon">&#128279;</span>
                            <span id="ma-copy-btn-text">Share Link</span>
                        </button>
                    </div>
                    <div class="ma-header-status-group">
                        <span class="ma-result-status-title" id="val-status-header">Status: -</span>
                    </div>
                </div>

                <!-- Quick Route Overview Card -->
                <div class="ma-route-card">
                    <div class="ma-route-node">
                        <span class="ma-route-label">ORIGIN</span>
                        <span class="ma-route-city" id="val-overview-origin">-</span>
                    </div>
                    <div class="ma-route-transit-indicator">
                        <span class="ma-route-line"></span>
                        <span class="ma-route-truck" id="ma-route-icon">&#9992;</span>
                    </div>
                    <div class="ma-route-node ma-route-right">
                        <span class="ma-route-label">DESTINATION</span>
                        <span class="ma-route-city" id="val-overview-dest">-</span>
                    </div>
                </div>

                <!-- Double Column Grid (Shipment Details + Milestone Timeline) -->
                <div class="ma-result-body-grid">
                    
                    <!-- Left Column: Bordered Tracking Info Table -->
                    <div class="ma-info-column">
                        <h3 class="ma-section-title">Consignment Details</h3>
                        <table class="ma-info-table">
                            <tbody>
                                <tr>
                                    <td class="label-cell">AWB No.</td>
                                    <td class="value-cell" id="val-awb-no">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Booking Date</td>
                                    <td class="value-cell" id="val-booking-date">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Consignor</td>
                                    <td class="value-cell" id="val-consignor">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Consignee</td>
                                    <td class="value-cell" id="val-consignee">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Origin</td>
                                    <td class="value-cell" id="val-origin">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Destination</td>
                                    <td class="value-cell" id="val-destination">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Total Pieces / Boxes</td>
                                    <td class="value-cell" id="val-pieces">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Current Status</td>
                                    <td class="value-cell status-highlight" id="val-status">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Expected Delivery</td>
                                    <td class="value-cell" id="val-expected-delivery">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Delivered Date & Time</td>
                                    <td class="value-cell" id="val-delivery-datetime">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Receiver Name</td>
                                    <td class="value-cell" id="val-receiver">-</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Docket / Forwarding No.</td>
                                    <td class="value-cell" id="val-forwarding">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Right Column: Shipment Tracking History -->
                    <div class="ma-timeline-column">
                        <div class="ma-history-header-card">
                            <div class="ma-history-title-group">
                                <h3 class="ma-history-title">
                                    <svg class="ma-title-clock-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    Shipment Tracking History
                                </h3>
                                <span class="ma-history-awb-badge" id="val-table-awb-badge">AWB: -</span>
                                <span class="ma-events-counter" id="val-events-count">0 Events</span>
                            </div>
                            <div class="ma-view-switcher">
                                <button type="button" class="ma-switcher-btn active" id="btn-show-table" onclick="maSwitchView('table')">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                                    Table View
                                </button>
                                <button type="button" class="ma-switcher-btn" id="btn-show-timeline" onclick="maSwitchView('timeline')">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="9"/><line x1="12" y1="15" x2="12" y2="22"/></svg>
                                    Timeline
                                </button>
                            </div>
                        </div>

                        <!-- 1. Table View (PRIMARY & DEFAULT) -->
                        <div class="ma-table-container" id="ma-history-table-box">
                            <table class="ma-history-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 16%;">DATE</th>
                                        <th style="width: 12%;">TIME</th>
                                        <th style="width: 18%;">LOCATION</th>
                                        <th style="width: 18%;">STATUS</th>
                                        <th style="width: 31%;">REMARKS</th>
                                    </tr>
                                </thead>
                                <tbody id="ma-tracking-table-rows">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- 2. Milestone Timeline (Alternative View) -->
                        <div class="ma-timeline-wrapper" id="ma-timeline-items" style="display: none;">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                </div>

                <!-- Result Footer -->
                <div class="ma-result-footer">
                    <span>MARL Express Pvt. Ltd. &bull; Live Shipment Tracking Engine</span>
                </div>

            </div>

        </div>
        <!-- ================== END CMS TRACKING COMPONENT ================== -->
        <?php
        return ob_get_clean();
    }
}

new MALogisticsTracking();
