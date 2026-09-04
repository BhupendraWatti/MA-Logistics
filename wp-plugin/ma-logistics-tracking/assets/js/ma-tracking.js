/**
 * MA Logistics Tracking — Dynamic Vanilla JavaScript Controller
 * Zero external dependencies (No jQuery required).
 */

(function () {
    'use strict';

    // Auto-detect API base URL from wp_localize_script or fallback
    const API_BASE = (typeof window.maTrackingConfig !== 'undefined' && window.maTrackingConfig.apiUrl)
        ? window.maTrackingConfig.apiUrl.replace(/\/+$/, '') + '/'
        : 'https://granthinfotech.online/api/track/';

    window.initiateTrack = function () {
        const inputField = document.getElementById('ma-awb-input');
        const errContainer = document.getElementById('ma-error-message');
        const loaderSpinner = document.getElementById('ma-btn-loader');
        const submitBtn = document.getElementById('ma-track-submit-btn');
        const resultBox = document.getElementById('ma-tracking-result-box');

        if (!inputField) return;
        const searchVal = inputField.value.trim();
        if (!searchVal) return;

        // Reset UI states
        if (errContainer) {
            errContainer.style.display = 'none';
            errContainer.innerHTML = '';
        }
        if (resultBox) {
            resultBox.style.display = 'none';
        }
        if (loaderSpinner) {
            loaderSpinner.style.display = 'inline-block';
        }
        if (submitBtn) {
            submitBtn.setAttribute('disabled', 'disabled');
        }

        // Execute API Request
        fetch(API_BASE + encodeURIComponent(searchVal))
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (errData) {
                        throw new Error(errData.message || 'No tracking records found.');
                    }).catch(function () {
                        throw new Error('Server returned error HTTP ' + response.status);
                    });
                }
                return response.json();
            })
            .then(function (res) {
                if (res.status === 'success' && res.data) {
                    renderTrackingData(res.data);
                    if (resultBox) {
                        resultBox.style.display = 'block';
                        resultBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    throw new Error(res.message || 'No tracking records found for this AWB/Docket.');
                }
            })
            .catch(function (err) {
                if (errContainer) {
                    errContainer.innerHTML = '&#9888;&nbsp; ' + escapeHtml(err.message || 'An unexpected network error occurred.');
                    errContainer.style.display = 'flex';
                    errContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            })
            .finally(function () {
                if (loaderSpinner) {
                    loaderSpinner.style.display = 'none';
                }
                if (submitBtn) {
                    submitBtn.removeAttribute('disabled');
                }
            });
    };

    /**
     * Populate DOM with tracking data
     */
    function renderTrackingData(data) {
        const b = data.booking || {};
        const h = Array.isArray(data.history) ? data.history : [];

        // Header Band
        setText('val-awb-header', 'AWB: ' + (b.awb_no || '-'));
        const statusHeader = document.getElementById('val-status-header');
        const currentStatus = b.current_status || b.latest_remark || 'In Transit';
        setText('val-status-header', 'Status: ' + currentStatus);

        // Status color-coding
        const isDelivered = (currentStatus.toUpperCase().indexOf('DELIVERED') !== -1);
        if (statusHeader) {
            if (isDelivered) {
                statusHeader.style.background = '#15803d';
            } else {
                statusHeader.style.background = '#1e293b';
            }
        }

        // Route overview
        setText('val-overview-origin', b.origin || 'Origin Hub');
        setText('val-overview-dest', b.destination || 'Destination Hub');

        // Left Column Table Fields
        setText('val-awb-no', b.awb_no || '-');
        setText('val-booking-date', b.booking_date || '-');
        setText('val-consignor', b.consignor_name || '-');
        setText('val-consignee', b.consignee_name || '-');
        setText('val-origin', b.origin || '-');
        setText('val-destination', b.destination || '-');
        setText('val-pieces', b.total_pieces || '-');
        
        const statusCell = document.getElementById('val-status');
        if (statusCell) {
            statusCell.innerText = currentStatus;
            statusCell.style.color = isDelivered ? '#16a34a' : '#2563eb';
        }

        // Expected Delivery
        let expectedText = '-';
        if (b.expected_delivery_date && b.expected_delivery_date !== '-') {
            expectedText = b.expected_delivery_date;
            if (b.expected_delivery_time && b.expected_delivery_time !== '-') {
                expectedText += ' at ' + b.expected_delivery_time;
            }
        }
        setText('val-expected-delivery', expectedText);

        // Delivered Date & Time
        let deliveryCombined = '-';
        if (b.delivery_date && b.delivery_date !== '-') {
            deliveryCombined = b.delivery_date;
            if (b.delivery_time && b.delivery_time !== '-') {
                deliveryCombined += ' ' + b.delivery_time;
            }
        }
        setText('val-delivery-datetime', deliveryCombined);

        setText('val-receiver', b.receiver_name || '-');
        setText('val-forwarding', b.forwarding_no || '-');

        // Right Column: Milestone Timeline
        setText('val-history-header-title', 'Tracking Events — AWB: ' + (b.awb_no || ''));
        setText('val-events-count', h.length + (h.length === 1 ? ' Event' : ' Events'));

        renderMilestoneTimeline(h);
    }

    /**
     * Render vertical milestone timeline items
     */
    function renderMilestoneTimeline(events) {
        const container = document.getElementById('ma-timeline-items');
        if (!container) return;

        container.innerHTML = '';

        if (!events || events.length === 0) {
            container.innerHTML = '<div class="ma-timeline-empty">&#128230; Package registered & dispatched. Real-time hub milestones will populate here as the consignment progresses.</div>';
            return;
        }

        events.forEach(function (ev, index) {
            const isDelivered = (ev.activity && ev.activity.toUpperCase().indexOf('DELIVERED') !== -1);
            const item = document.createElement('div');
            item.className = 'ma-timeline-item' + (isDelivered ? ' is-delivered' : '');

            let remarksHtml = '';
            if (ev.remarks && ev.remarks.trim() !== '-') {
                remarksHtml += '<div class="ma-timeline-remarks">' + escapeHtml(ev.remarks);
                if (ev.receiver_name) {
                    remarksHtml += ' <span style="font-weight:600;">(Received by: ' + escapeHtml(ev.receiver_name) + ')</span>';
                }
                remarksHtml += '</div>';
            } else if (ev.receiver_name) {
                remarksHtml += '<div class="ma-timeline-remarks"><span style="font-weight:600;">Received by: ' + escapeHtml(ev.receiver_name) + '</span></div>';
            }

            item.innerHTML = 
                '<div class="ma-timeline-dot"></div>' +
                '<div class="ma-timeline-top">' +
                    '<span class="ma-timeline-activity">' + escapeHtml(ev.activity || 'Status Update') + '</span>' +
                    '<span class="ma-timeline-badge">' + escapeHtml(ev.location || 'Hub') + '</span>' +
                '</div>' +
                '<div class="ma-timeline-meta">' +
                    '<span>&#128197; ' + escapeHtml(ev.date || '-') + '</span>' +
                    '<span>&#9200; ' + escapeHtml(ev.time || '-') + '</span>' +
                '</div>' +
                remarksHtml;

            container.appendChild(item);
        });
    }

    /**
     * Copy shareable link to clipboard
     */
    window.copyTrackingLink = function () {
        const inputField = document.getElementById('ma-awb-input');
        const btnText = document.getElementById('ma-copy-btn-text');
        if (!inputField || !inputField.value.trim()) return;

        const awb = inputField.value.trim();
        const url = new URL(window.location.href);
        url.searchParams.set('awb', awb);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url.toString()).then(function () {
                if (btnText) {
                    btnText.innerText = 'Copied!';
                    setTimeout(function () {
                        btnText.innerText = 'Share Link';
                    }, 2500);
                }
            });
        } else {
            prompt('Copy this tracking link:', url.toString());
        }
    };

    /**
     * Helpers
     */
    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.innerText = text;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * On Page Load: Deep Linking Auto-Tracking (URL Parameters: ?awb=... or ?docket=...)
     */
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const autoQuery = params.get('awb') || params.get('docket') || params.get('tracking_no');

        if (autoQuery) {
            const inputField = document.getElementById('ma-awb-input');
            if (inputField) {
                inputField.value = autoQuery.trim();
                setTimeout(function () {
                    window.initiateTrack();
                }, 150);
            }
        }
    });

})();
