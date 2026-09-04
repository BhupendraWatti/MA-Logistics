<?php
/**
 * Plugin Name: MA Logistics Tracking
 * Plugin URI:  https://granthinfotech.online/
 * Description: High-performance, mobile-responsive live shipment tracking component for MA Logistics ERP with URL deep-linking and interactive milestone timeline.
 * Version:     1.0.0
 * Author:      MARL Express & Granth Infotech
 * Author URI:  https://granthinfotech.in/
 * License:     GPL-2.0+
 * Text Domain: ma-tracking
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('MA_TRACKING_VERSION', '1.0.0');
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
     * - title: Form title (default: Track Shipment)
     * - subtitle: Form subtitle
     * - placeholder: Input placeholder
     * - button_text: Button text
     * - primary_color: Accent color (default: #2563eb)
     */
    public function render_shortcode($atts = []) {
        $atts = shortcode_atts([
            'api_url'       => 'https://granthinfotech.online/api/track/',
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
            'apiUrl' => trailingslashit(esc_url_raw($atts['api_url'])),
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

                    <!-- Right Column: Interactive Milestone Timeline -->
                    <div class="ma-timeline-column">
                        <div class="ma-timeline-header-row">
                            <h3 class="ma-section-title" id="val-history-header-title">Tracking Milestone Events</h3>
                            <span class="ma-events-counter" id="val-events-count">0 Events</span>
                        </div>

                        <!-- Modern Milestone Timeline List -->
                        <div class="ma-timeline-wrapper" id="ma-timeline-items">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <!-- Fallback Table View (Toggleable or Mobile View) -->
                        <div class="ma-table-container" id="ma-history-table-box" style="display: none;">
                            <table class="ma-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Location</th>
                                        <th>Activity</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="ma-tracking-table-rows">
                                    <!-- Dynamic rows go here -->
                                </tbody>
                            </table>
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
