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

    const SITE_BASE = (typeof window.maTrackingConfig !== 'undefined' && window.maTrackingConfig.siteUrl)
        ? window.maTrackingConfig.siteUrl.replace(/\/+$/, '') + '/'
        : (window.location.origin + '/');

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
        const requestUrl = API_BASE + encodeURIComponent(searchVal);

        fetch(requestUrl)
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (errData) {
                        throw new Error(errData.message || ('No tracking records found for AWB/Docket: ' + searchVal));
                    }).catch(function (e) {
                        if (e && e.message && e.message.indexOf('No tracking') !== -1) {
                            throw e;
                        }
                        throw new Error('Server returned HTTP ' + response.status + '. Please try again.');
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
                    const msg = res.message || ('No shipment records found for AWB/Docket: ' + searchVal);
                    throw new Error(msg);
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
            statusCell.style.color = isDelivered ? '#15803d' : '#2563eb';
            statusCell.style.fontWeight = '800';
        }

        // Route transit icon position
        const truck = document.querySelector('.ma-route-truck');
        if (truck) {
            truck.style.left = isDelivered ? 'calc(100% - 14px)' : '50%';
        }

        // Expected Delivery
        let expectedText = '-';
        if (b.expected_delivery_date && b.expected_delivery_date !== '-') {
            expectedText = b.expected_delivery_date;
            if (b.expected_delivery_time && b.expected_delivery_time !== '-') {
                let expTime = b.expected_delivery_time;
                if (expTime.length > 5) expTime = expTime.substring(0, 5);
                expectedText += ' at ' + expTime;
            }
        }
        setText('val-expected-delivery', expectedText);

        // Delivered Date & Time
        let deliveryCombined = '-';
        if (b.delivery_date && b.delivery_date !== '-') {
            deliveryCombined = b.delivery_date;
            if (b.delivery_time && b.delivery_time !== '-') {
                let delTime = b.delivery_time;
                if (delTime.length > 5) delTime = delTime.substring(0, 5);
                deliveryCombined += ' at ' + delTime;
            }
        } else {
            // Check history for any delivered event
            for (let i = 0; i < h.length; i++) {
                const act = (h[i].status || h[i].activity || '').toUpperCase();
                if (act.indexOf('DELIVERED') !== -1) {
                    deliveryCombined = h[i].date || '-';
                    if (h[i].time && h[i].time !== '-') {
                        let delTime = h[i].time;
                        if (delTime.length > 5) delTime = delTime.substring(0, 5);
                        deliveryCombined += ' at ' + delTime;
                    }
                    break;
                }
            }
        }
        const delCell = document.getElementById('val-delivery-datetime');
        if (delCell) {
            delCell.innerText = deliveryCombined;
            if (isDelivered && deliveryCombined !== '-') {
                delCell.style.color = '#15803d';
                delCell.style.fontWeight = '700';
            } else {
                delCell.style.color = '';
                delCell.style.fontWeight = '';
            }
        }

        setText('val-receiver', b.receiver_name || '-');
        setText('val-forwarding', b.forwarding_no || '-');

        // Right Column: Shipment Tracking History
        setText('val-table-awb-badge', 'AWB: ' + (b.awb_no || '-'));
        setText('val-events-count', h.length + (h.length === 1 ? ' Event' : ' Events'));

        // Render both Table and Timeline views
        renderTrackingTable(h, b.awb_no);
        renderMilestoneTimeline(h);

        // Ensure Table view is the default active view
        window.maSwitchView('table');
    }

    /**
     * Render vertical milestone timeline items
     */
    /**
     * Switch between Table View and Timeline View
     */
    window.maSwitchView = function (viewType) {
        const tableBox = document.getElementById('ma-history-table-box');
        const timelineBox = document.getElementById('ma-timeline-items');
        const btnTable = document.getElementById('btn-show-table');
        const btnTimeline = document.getElementById('btn-show-timeline');

        if (viewType === 'table') {
            if (tableBox) tableBox.style.display = 'block';
            if (timelineBox) timelineBox.style.display = 'none';
            if (btnTable) btnTable.classList.add('active');
            if (btnTimeline) btnTimeline.classList.remove('active');
        } else {
            if (tableBox) tableBox.style.display = 'none';
            if (timelineBox) timelineBox.style.display = 'flex';
            if (btnTable) btnTable.classList.remove('active');
            if (btnTimeline) btnTimeline.classList.add('active');
        }
    };

    /**
     * Render Shipment Tracking History Table (matching ERP Drawer specification)
     */
    function renderTrackingTable(events, awbNo) {
        const tbody = document.getElementById('ma-tracking-table-rows');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!events || events.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="ma-table-empty-cell">' +
                '<div class="ma-empty-table-wrap">' +
                    '<span class="ma-empty-table-icon">&#128230;</span>' +
                    '<div>No tracking history available for this consignment. Real-time milestones will appear as the consignment progresses.</div>' +
                '</div>' +
            '</td></tr>';
            return;
        }

        events.forEach(function (ev, index) {
            const tr = document.createElement('tr');

            // # (Index)
            const tdNum = document.createElement('td');
            tdNum.className = 'ma-td-num';
            tdNum.textContent = index + 1;
            tr.appendChild(tdNum);

            // Date
            const tdDate = document.createElement('td');
            tdDate.className = 'ma-td-date';
            tdDate.textContent = ev.date || '-';
            tr.appendChild(tdDate);

            // Time (HH:mm)
            const tdTime = document.createElement('td');
            tdTime.className = 'ma-td-time';
            let displayTime = ev.time || '-';
            if (displayTime && displayTime.length > 5) {
                displayTime = displayTime.substring(0, 5);
            }
            tdTime.textContent = displayTime;
            tr.appendChild(tdTime);

            // Location with pin icon
            const tdLoc = document.createElement('td');
            tdLoc.className = 'ma-td-location';
            tdLoc.innerHTML = '<span class="ma-loc-pin-svg"><svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span> <strong>' + escapeHtml(ev.location || '-') + '</strong>';
            tr.appendChild(tdLoc);

            // Status Badge
            const tdStatus = document.createElement('td');
            tdStatus.className = 'ma-td-status';
            const statusStr = (ev.status || ev.activity || '').trim();
            const lower = statusStr.toLowerCase();
            let badgeClass = 'status-default';
            if (lower.indexOf('arrived') !== -1) {
                badgeClass = 'status-arrived';
            } else if (lower.indexOf('transit') !== -1) {
                badgeClass = 'status-transit';
            } else if (lower.indexOf('deliver') !== -1) {
                badgeClass = 'status-delivered';
            } else if (lower.indexOf('pick') !== -1) {
                badgeClass = 'status-picked';
            } else if (lower.indexOf('book') !== -1 || lower.indexOf('active') !== -1) {
                badgeClass = 'status-booked';
            }
            tdStatus.innerHTML = '<span class="ma-status-pill ' + badgeClass + '">' + escapeHtml(statusStr || '-') + '</span>';
            tr.appendChild(tdStatus);

            // Remarks + Receiver
            const tdRemarks = document.createElement('td');
            tdRemarks.className = 'ma-td-remarks';
            let remarksText = (ev.remarks && ev.remarks.trim() && ev.remarks.trim() !== '-') ? escapeHtml(ev.remarks) : '-';
            let receiverHtml = '';
            if (ev.receiver_name && ev.receiver_name.trim() && ev.receiver_name.trim() !== '-') {
                receiverHtml = '<div class="ma-table-receiver"><svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Receiver: <strong>' + escapeHtml(ev.receiver_name) + '</strong></div>';
            }
            tdRemarks.innerHTML = '<div class="ma-remarks-block"><span>' + remarksText + '</span>' + receiverHtml + '</div>';
            tr.appendChild(tdRemarks);

            tbody.appendChild(tr);
        });
    }

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
