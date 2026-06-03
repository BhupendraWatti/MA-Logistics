<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Tracking - MARL Express</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary: #475569;
            --bg-gradient: linear-gradient(135deg, #0f172a, #1e293b);
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: var(--text-main);
        }

        /* Premium Header Styling */
        header {
            background: var(--bg-gradient);
            padding: 20px 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .back-to-erp {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-to-erp:hover {
            color: #ffffff;
        }

        /* Demo Dashboard Grid */
        .main-content {
            flex-grow: 1;
            max-width: 1300px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Search Panel Container */
        .ma-search-wrapper {
            background: #ffffff;
            border-radius: 16px;
            padding: 35px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .ma-search-wrapper {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .ma-search-left {
            flex: 1;
        }
        .ma-search-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        .ma-search-subtitle {
            font-size: 13px;
            color: #64748b;
        }

        .ma-search-right {
            flex: 1.2;
            width: 100%;
            max-width: 600px;
        }

        /* Dropdown & Input search group */
        .ma-search-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        @media (min-width: 576px) {
            .ma-search-form {
                flex-direction: row;
                align-items: stretch;
            }
        }

        .ma-select-wrapper {
            position: relative;
            min-width: 140px;
        }

        .ma-search-select {
            width: 100%;
            height: 100%;
            padding: 12px 35px 12px 15px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            background-color: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            appearance: none;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .ma-search-select:focus {
            border-color: var(--primary);
            background-color: #ffffff;
        }

        .ma-select-arrow {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            font-size: 12px;
        }

        .ma-input-group {
            display: flex;
            align-items: center;
            position: relative;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            padding: 4px 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
            flex: 1;
        }
        .ma-input-group:focus-within {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }
        .ma-input-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px 0 15px;
            color: #94a3b8;
        }
        .ma-tracking-input {
            flex: 1;
            border: none !important;
            background: transparent !important;
            padding: 10px 5px !important;
            font-size: 15px !important;
            color: #1e293b !important;
            outline: none !important;
            box-shadow: none !important;
            width: 100%;
        }
        .ma-tracking-btn {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .ma-tracking-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }

        /* Spinner Loader */
        .ma-loader {
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            display: none;
            margin-left: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Playbook Quick badging */
        .ma-demo-playbook {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            background: #eff6ff;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid #dbeafe;
            font-size: 13px;
        }
        .ma-playbook-label {
            font-weight: 700;
            color: #1e40af;
        }
        .ma-playbook-badge {
            background: #ffffff;
            border: 1px solid #bfdbfe;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #2563eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .ma-playbook-badge:hover {
            background: #dbeafe;
            transform: translateY(-1px);
        }

        /* Form Errors */
        .ma-error-container {
            color: #dc2626;
            font-size: 13px;
            font-weight: 500;
            padding: 10px 20px;
            background: #fef2f2;
            border-radius: 8px;
            border: 1px solid #fee2e2;
            display: none;
        }

        /* ================== INLINE RESULT CONTAINER (NO POPUP!) ================== */
        .ma-result-wrapper {
            display: none; /* Initially hidden */
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0,0,0,0.06);
            overflow: hidden;
            animation: slideDown 0.4s ease forwards;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header Band (Black Background) matching Screenshot */
        .ma-result-header-band {
            background: #000000;
            color: #ffffff;
            padding: 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-weight: 600;
        }

        @media (min-width: 576px) {
            .ma-result-header-band {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        .ma-result-awb-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .ma-result-status-title {
            font-size: 14px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: #1e293b;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 700;
        }

        /* Main Double Table Grid */
        .ma-result-body-grid {
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 992px) {
            .ma-result-body-grid {
                grid-template-columns: 2fr 3fr; /* 2 Column Layout */
            }
        }

        /* Columns Headers */
        .ma-table-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #f1f5f9;
        }

        /* Related details left table structure (matching screenshot) */
        .ma-info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .ma-info-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 13px;
            color: #334155;
        }
        .ma-info-table tr:last-child td {
            border-bottom: none;
        }
        .ma-info-table td.label-cell {
            font-weight: 600;
            background-color: #f8fafc;
            color: #475569;
            width: 40%;
            border-right: 1px solid #cbd5e1;
        }
        .ma-info-table td.value-cell {
            font-weight: 500;
            color: #0f172a;
            word-break: break-word;
        }

        /* Active highlight for Status field */
        .ma-info-table td.value-cell.status-highlight {
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
        }

        /* Tracking events right table structure (matching screenshot) */
        .ma-table-container {
            overflow-x: auto;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }

        .ma-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            text-align: left;
            min-width: 550px;
        }
        .ma-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 12px 14px;
            border-bottom: 2px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            font-size: 13px;
        }
        .ma-table th:last-child {
            border-right: none;
        }
        .ma-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
            white-space: normal;
        }
        .ma-table td:last-child {
            border-right: none;
        }
        .ma-table tr:last-child td {
            border-bottom: none;
        }
        .ma-table tr:hover {
            background-color: #f8fafc;
        }

        /* Status Badge Highlight */
        .status-badge {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
        }

        /* Dynamic result footer inside result container */
        .ma-result-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        /* Global Layout Footer */
        .global-footer {
            background: #0f172a;
            color: #94a3b8;
            text-align: center;
            padding: 20px;
            font-size: 13px;
            font-weight: 500;
            border-top: 1px solid #1e293b;
            margin-top: 40px;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header>
        <div class="logo-text">MARL Express</div>
        <a href="<?= base_url('logistics') ?>" class="back-to-erp">
            <i class="fa-solid fa-arrow-left-long"></i> Back to ERP Admin
        </a>
    </header>

    <!-- Main Grid Dashboard -->
    <div class="main-content">
        
        <!-- Search & Control Panel wrapper -->
        <div class="ma-search-wrapper">
            <div class="ma-search-left">
                <h2 class="ma-search-title">Track Shipment</h2>
                <p class="ma-search-subtitle">Enter your AWB No. or Docket No. to view real-time package logs inline.</p>
            </div>
            
            <div class="ma-search-right">
                <form id="ma-tracking-form" class="ma-search-form" onsubmit="event.preventDefault(); initiateTrack();">
                    <!-- Dropdown for AWB / Docket / Ref (Visual selector matching screenshot) -->
                    <div class="ma-select-wrapper">
                        <select class="ma-search-select" id="ma-search-type">
                            <option value="awb">AWB No.</option>
                            <option value="docket">Docket No.</option>
                        </select>
                        <span class="ma-select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
                    </div>

                    <div class="ma-input-group">
                        <span class="ma-input-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="ma-awb-input" class="ma-tracking-input" placeholder="e.g. PA1019318" required autocomplete="off">
                        <button type="submit" class="ma-tracking-btn">
                            <span>Track</span>
                            <div class="ma-loader" id="ma-btn-loader"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Playbook Live helper -->
        <div class="ma-demo-playbook">
            <span class="ma-playbook-label"><i class="fa-solid fa-database me-1"></i> Live Playbook:</span>
            <span class="ma-playbook-badge" onclick="selectSample('PA1019318')">PA1019318 (Delivered AWB)</span>
            <span class="ma-playbook-badge" onclick="selectSample('MA-10001')">MA-10001 (Billed AWB)</span>
            <span class="ma-playbook-badge" onclick="selectSample('MA-10002')">MA-10002 (Billed AWB)</span>
            <span class="ma-playbook-badge" onclick="selectSample('4561712')">4561712 (Docket / AWB)</span>
        </div>

        <!-- Error Notification box -->
        <div id="ma-error-message" class="ma-error-container"></div>

        <!-- ================== INLINE SHIELD INSTEAD OF MODAL ================== -->
        <div id="ma-tracking-result-box" class="ma-result-wrapper">
            
            <!-- Black Header Band exactly matching shared layout -->
            <div class="ma-result-header-band">
                <span class="ma-result-awb-title" id="val-awb-header">AWB: PA1019318</span>
                <span class="ma-result-expected-title d-none" id="val-expected-header" style="font-size: 13px; text-transform: uppercase; background: #2563eb; padding: 4px 12px; border-radius: 4px; font-weight: 700; color: #fff;">Expected Delivery: -</span>
                <span class="ma-result-status-title" id="val-status-header">Status: DELIVERED</span>
            </div>

            <!-- Double Table Grid Structure (100% responsive responsive grid) -->
            <div class="ma-result-body-grid">
                
                <!-- Left Column: Bordered Tracking Info Table -->
                <div>
                    <h3 class="ma-table-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Tracking Information</h3>
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
                                <td class="label-cell">Consignee Name</td>
                                <td class="value-cell" id="val-consignee">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Destination</td>
                                <td class="value-cell" id="val-destination">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">No. Of Pieces</td>
                                <td class="value-cell" id="val-pieces">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Status</td>
                                <td class="value-cell status-highlight" id="val-status">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Delivery Date</td>
                                <td class="value-cell" id="val-delivery-date">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Delivery Time</td>
                                <td class="value-cell" id="val-delivery-time">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Receiver Name</td>
                                <td class="value-cell" id="val-receiver">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Forwarding No.</td>
                                <td class="value-cell" id="val-forwarding">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right Column: Historical logs table -->
                <div>
                    <h3 class="ma-table-title" id="val-history-header-title"><i class="fa-solid fa-list-check me-2 text-primary"></i>AWB: PA1019318</h3>
                    <div class="ma-table-container">
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

            <!-- Dynamic footer inside the result container -->
            <div class="ma-result-footer">
                All Right Reserved @2026 Developed By - Granth Infotech Pvt. Ltd.
            </div>

        </div>

    </div>

    <!-- Global Layout Footer -->
    <footer class="global-footer">
        All Right Reserved @2026 Developed By - Granth Infotech Pvt. Ltd.
    </footer>

    <!-- ================== DYNAMIC JAVASCRIPT LOGIC ================== -->
    <script>
        // API Base Point - points to the newly developed CI4 controller API
        const MA_ERP_API_BASE = "<?= base_url('api/track/') ?>/";

        function selectSample(value) {
            document.getElementById("ma-awb-input").value = value;
            const selectType = document.getElementById("ma-search-type");
            if (value === '4561712') {
                selectType.value = 'docket';
            } else {
                selectType.value = 'awb';
            }
            initiateTrack();
        }

        function initiateTrack() {
            const inputField = document.getElementById("ma-awb-input");
            const errContainer = document.getElementById("ma-error-message");
            const loaderSpinner = document.getElementById("ma-btn-loader");
            const resultBox = document.getElementById("ma-tracking-result-box");
            const searchVal = inputField.value.trim();

            if (!searchVal) return;

            // Reset error container state
            errContainer.style.display = "none";
            errContainer.innerHTML = "";
            resultBox.style.display = "none"; // Hide previous search results

            // Show loading spinner inside the button
            loaderSpinner.style.display = "inline-block";

            const searchType = document.getElementById("ma-search-type").value;

            // Call public API endpoint using Ajax Fetch
            // We pass searchType as both a path segment and a query string parameter to ensure 
            // 100% compatibility across diverse hosting server configurations (e.g. Apache query rewriting vs path segments)
            fetch(MA_ERP_API_BASE + encodeURIComponent(searchType) + "/" + encodeURIComponent(searchVal) + "?type=" + encodeURIComponent(searchType))
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errData => {
                            throw new Error(errData.message || "Unable to fetch tracking data.");
                        });
                    }
                    return response.json();
                })
                .then(res => {
                    if (res.status === "success" && res.data) {
                        populateInlineData(res.data);
                        // Smoothly scroll to results
                        resultBox.style.display = "block";
                        resultBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        throw new Error("No tracking records were found.");
                    }
                })
                .catch(err => {
                    // Display error cleanly
                    errContainer.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${err.message || "An unexpected network or server error occurred."}`;
                    errContainer.style.display = "block";
                })
                .finally(() => {
                    // Hide loading spinner
                    loaderSpinner.style.display = "none";
                });
        }

        function populateInlineData(data) {
            const b = data.booking;
            const h = data.history;

            // Set Header Band text
            document.getElementById("val-awb-header").innerText = "AWB: " + b.awb_no;
            document.getElementById("val-status-header").innerText = "Status: " + b.current_status;

            const expectedHeader = document.getElementById("val-expected-header");
            if (b.expected_delivery_date && b.expected_delivery_date !== '-') {
                let expText = "Expected Delivery: " + b.expected_delivery_date;
                if (b.expected_delivery_time && b.expected_delivery_time !== '-') {
                    expText += " " + b.expected_delivery_time;
                }
                expectedHeader.innerText = expText;
                expectedHeader.classList.remove('d-none');
            } else {
                expectedHeader.classList.add('d-none');
            }

            // Update Right Side Table header
            document.getElementById("val-history-header-title").innerHTML = `<i class="fa-solid fa-list-check me-2 text-primary"></i>AWB: ${b.awb_no}`;

            // Populate Left Side Bordered Info Table
            document.getElementById("val-awb-no").innerText = b.awb_no;
            document.getElementById("val-booking-date").innerText = b.booking_date;
            document.getElementById("val-consignee").innerText = b.consignee_name;
            document.getElementById("val-destination").innerText = b.destination;
            document.getElementById("val-pieces").innerText = b.total_pieces;
            document.getElementById("val-status").innerText = b.current_status;
            document.getElementById("val-delivery-date").innerText = b.delivery_date;
            document.getElementById("val-delivery-time").innerText = b.delivery_time;
            document.getElementById("val-receiver").innerText = b.receiver_name;
            document.getElementById("val-forwarding").innerText = b.forwarding_no;

            // Visual Status highlight rules
            const statusCell = document.getElementById("val-status");
            const statusHeader = document.getElementById("val-status-header");
            const isDelivered = b.current_status.toUpperCase().includes('DELIVERED');
            
            if (isDelivered) {
                statusCell.style.color = "#16a34a"; // Green
                statusHeader.style.background = "#15803d"; // Deep Green
            } else {
                statusCell.style.color = "#2563eb"; // Blue
                statusHeader.style.background = "#1e293b"; // Dark Slate
            }

            // Populate Right Side Tracking History rows
            const tbody = document.getElementById("ma-tracking-table-rows");
            tbody.innerHTML = ""; // Clean standard row elements

            if (h.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #64748b; padding: 30px;"><i class="fa-regular fa-folder-open me-2"></i>No historical tracking events found for this booking yet.</td></tr>`;
                return;
            }

            h.forEach(row => {
                const tr = document.createElement("tr");
                const rowDelivered = row.activity.includes('DELIVERED');
                
                tr.innerHTML = `
                    <td style="font-weight: 500;">${row.date}</td>
                    <td style="color: #475569;">${row.time}</td>
                    <td style="font-weight: 600; color: #1e293b;">${row.location}</td>
                    <td>
                        <span class="status-badge" style="
                            background-color: ${rowDelivered ? '#dcfce7' : '#f1f5f9'};
                            color: ${rowDelivered ? '#15803d' : '#334155'};
                            border: 1px solid ${rowDelivered ? '#bbf7d0' : '#cbd5e1'};
                        ">${row.activity}</span>
                    </td>
                    <td style="color: #64748b; font-style: italic;">${row.remarks || '-'}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    </script>
</body>
</html>
