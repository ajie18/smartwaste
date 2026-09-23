<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>CleanCity · Waste Reporting and Collection System</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script>
        (function() {
            var saved = localStorage.getItem('resident_theme');
            if (saved === 'light' || saved === 'dark') {
                document.documentElement.setAttribute('data-theme', saved);
            }
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/resident/dashboard.css') }}">
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <div class="brand-mark">C</div>
                <div class="brand-text">
                    <strong>CleanCity</strong>
                    <span>Resident Portal</span>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeSidebar()" aria-label="Close menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <nav class="sidebar-nav">
            <a href="#home" class="nav-link active" data-nav>Home</a>
            <a href="#about" class="nav-link" data-nav>About</a>
            <a href="#schedule" class="nav-link" data-nav>Schedule</a>
            <a href="#updates" class="nav-link" data-nav>Updates</a>
            <a href="#howto" class="nav-link" data-nav>How it works</a>
            <a href="#tips" class="nav-link" data-nav>Tips</a>
            <a href="#faq" class="nav-link" data-nav>FAQ</a>
            <a href="#report" class="nav-link" data-nav>Report Issue</a>
            <div class="nav-divider"></div>
            <button type="button" class="nav-link" onclick="openModal('profileModal')">Profile</button>
            <button type="button" class="nav-link" onclick="openModal('termsModal')">Terms & Privacy</button>
            <button type="button" class="nav-link theme-row" onclick="toggleTheme()">
                <span id="themeLabelMobile">Theme</span>
                <span class="theme-switch"><span class="theme-knob"></span></span>
            </button>
        </nav>
        <div class="sidebar-footer">
            <div class="user-row">
                <div class="avatar" id="userInitial">?</div>
                <div>
                    <div class="user-name" id="navUserName">Loading…</div>
                    <div class="user-role">Resident</div>
                </div>
            </div>
            <a href="/login.html" class="btn-logout">Sign out</a>
        </div>
    </aside>

    <header class="topbar">
        <div class="topbar-inner container-wide">
            <div class="topbar-left">
                <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false"
                    aria-controls="sidebar">
                    <span></span><span></span><span></span>
                </button>
                <a href="#home" class="brand desktop-only">
                    <div class="brand-mark">C</div>
                    <div class="brand-text">
                        <strong>CleanCity</strong>
                        <span>Waste Collection</span>
                    </div>
                </a>
            </div>
            <nav class="top-nav desktop-only">
                <a href="#home" class="top-link active" data-nav>Home</a>
                <a href="#about" class="top-link" data-nav>About</a>
                <a href="#schedule" class="top-link" data-nav>Schedule</a>
                <a href="#updates" class="top-link" data-nav>Updates</a>
                <a href="#howto" class="top-link" data-nav>How it works</a>
                <a href="#tips" class="top-link" data-nav>Tips</a>
                <a href="#faq" class="top-link" data-nav>FAQ</a>
                <a href="#report" class="top-link" data-nav>Report</a>
            </nav>
            <div class="topbar-right">
                <button type="button" class="icon-btn" id="themeToggleBtn" onclick="toggleTheme()"
                    title="Toggle theme">
                    <span id="themeToggleIcon"></span>
                </button>
                <button type="button" class="user-chip" onclick="openModal('profileModal')">
                    <span class="avatar sm" id="userInitialTop">?</span>
                    <span class="user-chip-name" id="navUserNameTop">…</span>
                </button>
            </div>
        </div>
    </header>

    <main class="main">
        <!-- HOME / LIVE MAP -->
        <section id="home" class="section">
            <div class="container-wide">
                <div class="hero-grid">
                    <div class="hero-copy">
                        <p class="eyebrow">Live tracking</p>
                        <h1 class="page-title">Know when collection is on the way</h1>
                        <p class="page-desc">Follow your assigned truck in real time, check this week’s schedule, and
                            report problems with a photo and pin — all from one place.</p>
                        <div class="stat-row">
                            <div class="stat"><strong>Live</strong><span>GPS updates</span></div>
                            <div class="stat"><strong>Photo</strong><span>issue reports</span></div>
                            <div class="stat"><strong>Zone</strong><span>schedules</span></div>
                        </div>
                    </div>
                    <div class="status-pill" id="truckStatusBox">
                        <span class="status-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" />
                                <path d="M15 18H9" />
                                <path
                                    d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" />
                                <circle cx="17" cy="18" r="2" />
                                <circle cx="7" cy="18" r="2" />
                            </svg>
                        </span>
                        <div>
                            <div class="status-label">Today’s truck</div>
                            <div class="status-value" id="todayTruck">Loading…</div>
                        </div>
                    </div>
                </div>

                <div class="banner" id="collectionBanner">
                    <div class="banner-icon" id="cbIcon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </div>
                    <div>
                        <div class="banner-title" id="cbTitle">Today's Collection Complete</div>
                        <div class="banner-sub" id="cbSub">Thanks for keeping CleanCity clean</div>
                    </div>
                </div>

                <div class="card map-card">
                    <div class="card-toolbar">
                        <div class="toolbar-label"><span class="dot"></span> Real-time map</div>
                        <div class="toolbar-actions">
                            <button class="chip active" id="btnStreet" onclick="setTile('street')">Map</button>
                            <button class="chip" id="btnSat" onclick="setTile('satellite')">Satellite</button>
                            <button class="chip" id="btnCenter" onclick="centerOnUser()">My location</button>
                            <button class="chip" id="btnShare" onclick="toggleShareLocation()">Share</button>
                        </div>
                    </div>
                    <div id="map"></div>
                    <div class="map-meta">
                        <div class="meta-row" id="locationInfo">
                            <span>Getting your location…</span>
                            <span id="locAccuracy"></span>
                        </div>
                        <div class="meta-row" id="distanceHUD" style="display:none;">
                            <span id="distanceText">Calculating distance…</span>
                            <span id="shareStatus">Sharing: OFF</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ABOUT -->
        <section id="about" class="section section-muted">
            <div class="container-wide">
                <div class="section-head">
                    <p class="eyebrow">Why CleanCity</p>
                    <h2 class="section-title">Built for residents, not just the depot</h2>
                    <p class="page-desc">CleanCity connects households with collection crews — live routes, pickup
                        days, and quick issue reports.</p>
                </div>
                <div class="grid-4">
                    <div class="feature-card">
                        <div class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg></div>
                        <h3>Live truck map</h3>
                        <p>When a route is active, the map shows the truck position and progress along the planned path.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg></div>
                        <h3>Zone schedules</h3>
                        <p>Your neighborhood’s collection days and truck plate appear here so you know what to expect.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                <circle cx="12" cy="13" r="4" />
                            </svg></div>
                        <h3>Photo reports</h3>
                        <p>Missed pickup, overflow, or dumping — attach a photo and GPS pin so crews can respond faster.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><svg width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg></div>
                        <h3>Announcements</h3>
                        <p>Holiday changes, temporary delays, and community notices from the waste office.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SCHEDULE -->
        <section id="schedule" class="section">
            <div class="container-wide">
                <div class="section-head">
                    <p class="eyebrow">Pickup schedule</p>
                    <h2 class="section-title">Your collection calendar</h2>
                </div>
                <div id="scheduleCards" class="stack"></div>
                <button type="button" class="link-btn see-all-btn" onclick="toggleScheduleAll()">Show all</button>
            </div>
        </section>

        <!-- UPDATES -->
        <section id="updates" class="section section-muted">
            <div class="container-wide">
                <div class="section-head">
                    <p class="eyebrow">Announcements</p>
                    <h2 class="section-title">From the waste office</h2>
                </div>
                <div id="announcementCards" class="grid-3-cards"></div>
                <button type="button" class="link-btn" id="annSeeAll" onclick="toggleAnnAll()">Show all</button>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section id="howto" class="section">
            <div class="container-wide">
                <div class="section-head center">
                    <p class="eyebrow">How it works</p>
                    <h2 class="section-title">Three steps to stay informed</h2>
                </div>
                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <h3>Open the map</h3>
                        <p>When collection is scheduled, check Home for today’s truck and live position once the route
                            starts.</p>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <h3>Follow the route</h3>
                        <p>Stops and progress appear on the map. You can share your location so crews know who is
                            nearby.</p>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <h3>Report if needed</h3>
                        <p>Something wrong? Use Report with a photo and location — the team gets a clear, actionable
                            ticket.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- TIPS -->
        <section id="tips" class="section section-muted">
            <div class="container-wide">
                <div class="section-head">
                    <p class="eyebrow">Eco tips</p>
                    <h2 class="section-title">Small habits, cleaner streets</h2>
                </div>
                <div class="grid-4">
                    <div class="tip">
                        <div class="tip-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M7 19a4 4 0 0 1-2.5-7.1L7 7l2.5 2.5" />
                                <path d="M17 5a4 4 0 0 1 2.5 7.1L17 17l-2.5-2.5" />
                                <path d="M12 2v4M12 18v4M4.9 7.5l2.1 3.5M17 13l2.1 3.5" />
                            </svg></div>
                        <h3>Segregate at home</h3>
                        <p>Keep recyclables, organics, and residual waste separate so processing stays efficient.</p>
                    </div>
                    <div class="tip">
                        <div class="tip-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
                            </svg></div>
                        <h3>Compost organics</h3>
                        <p>Food scraps can become soil instead of landfill — less methane, more nutrients.</p>
                    </div>
                    <div class="tip">
                        <div class="tip-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                                <line x1="12" y1="22.08" x2="12" y2="12" />
                            </svg></div>
                        <h3>Cut packaging</h3>
                        <p>Reusable bags and bulk buys mean fewer bags on the curb every week.</p>
                    </div>
                    <div class="tip">
                        <div class="tip-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg></div>
                        <h3>Set out on time</h3>
                        <p>Place bins before the truck arrives for your zone so nothing is left behind.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="section">
            <div class="container-wide">
                <div class="section-head center">
                    <p class="eyebrow">FAQ</p>
                    <h2 class="section-title">Common questions</h2>
                </div>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3>Why don’t I see a truck on the map?</h3>
                        <p>The map shows a live pin only after the assigned route has started and the driver has GPS.
                            Before that you’ll see status like “Not on route yet.”</p>
                    </div>
                    <div class="faq-item">
                        <h3>What if collection was missed?</h3>
                        <p>Use Report → Missed Pickup, add a short description, photo, and your location so the office
                            can follow up.</p>
                    </div>
                    <div class="faq-item">
                        <h3>Is my location shared all the time?</h3>
                        <p>No. Sharing is optional and only active while you turn on Share on the map. You can switch it
                            off anytime.</p>
                    </div>
                    <div class="faq-item">
                        <h3>Who sees my reports?</h3>
                        <p>Waste management staff use them to resolve issues. Reports aren’t sold or used for
                            advertising.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- REPORT -->
        <section id="report" class="section section-muted">
            <div class="container-wide">
                <div class="section-head">
                    <p class="eyebrow">Report an issue</p>
                    <h2 class="section-title">Something wrong on your street?</h2>
                    <p class="page-desc">Send a clear report with photo and pin so the team can respond.</p>
                </div>
                <div class="report-grid">
                    <div class="type-list">
                        <div class="type-item"><span class="dot amber"></span>
                            <div><strong>Missed pickup</strong><span>Scheduled collection didn’t happen</span></div>
                        </div>
                        <div class="type-item"><span class="dot red"></span>
                            <div><strong>Illegal dumping</strong><span>Unauthorized waste left in public areas</span>
                            </div>
                        </div>
                        <div class="type-item"><span class="dot green"></span>
                            <div><strong>Overflowing bin</strong><span>Full bin that needs urgent collection</span>
                            </div>
                        </div>
                        <div class="type-item"><span class="dot blue"></span>
                            <div><strong>Other</strong><span>Anything else that needs attention</span></div>
                        </div>
                        <div class="help-box">
                            <strong>Tip</strong>
                            <p>Clear photos and an accurate pin help crews find the issue faster — especially for
                                dumping or side streets.</p>
                        </div>
                    </div>
                    <div class="card form-card">
                        <form id="reportForm" onsubmit="handleReportSubmit(event)">
                            <div class="field">
                                <label class="label">Issue type</label>
                                <select id="rtype" class="input">
                                    <option value="missed_pickup">Missed Pickup</option>
                                    <option value="illegal_dumping">Illegal Dumping</option>
                                    <option value="overflowing_bin">Overflowing Bin</option>
                                    <option value="other">Other Issue</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="label">Description</label>
                                <textarea id="rdesc" class="input" placeholder="What happened, and where exactly?" maxlength="500" required
                                    oninput="updateCharCount()"></textarea>
                                <div class="hint" id="charCount">0 / 500</div>
                            </div>
                            <div class="field">
                                <label class="label">Photo <span class="req">required</span></label>
                                <div class="dropzone" id="photoDrop">
                                    <input type="file" id="photoInput" accept="image/*" capture="environment"
                                        onchange="handlePhotoChange(event)">
                                    <div class="drop-title">Tap for camera or gallery</div>
                                    <div class="drop-sub">JPG or PNG — we’ll compress it</div>
                                </div>
                                <div class="preview" id="photoPreviewWrap" style="display:none;">
                                    <img id="photoPreviewImg" src="" alt="Preview">
                                    <button type="button" class="remove-btn" onclick="removePhoto()">Remove</button>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Location <span class="req">required</span></label>
                                <div class="loc-row" id="locRow">
                                    <span class="loc-dot"></span>
                                    <span id="locText">Location not yet captured</span>
                                    <button type="button" class="loc-btn" onclick="captureLocation()">Get
                                        location</button>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary" id="submitBtn">Submit report</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container-wide footer-grid">
            <div class="footer-brand">
                <strong>CleanCity</strong>
                <span>Waste Reporting and Collection System</span>
                <p class="footer-blurb">Track collection and report issues so streets stay cleaner.</p>
            </div>
            <div class="footer-col">
                <h4>Explore</h4>
                <a href="#home">Live map</a>
                <a href="#schedule">Schedule</a>
                <a href="#updates">Updates</a>
                <a href="#report">Report</a>
            </div>
            <div class="footer-col">
                <h4>Help</h4>
                <a href="#faq">FAQ</a>
                <a href="#howto">How it works</a>
                <a href="javascript:void(0)" onclick="openModal('termsModal')">Terms & Privacy</a>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <a href="javascript:void(0)" onclick="openModal('profileModal')">Profile</a>
                <a href="/login.html">Sign out</a>
            </div>
            <!-- Compact links for phone -->
            <nav class="footer-mobile-links" aria-label="Footer">
                <a href="#home">Map</a>
                <a href="#schedule">Schedule</a>
                <a href="#report">Report</a>
                <a href="#faq">FAQ</a>
                <a href="javascript:void(0)" onclick="openModal('profileModal')">Profile</a>
                <a href="javascript:void(0)" onclick="openModal('termsModal')">Terms</a>
            </nav>
        </div>
        <div class="container-wide footer-bottom">
            <p class="copyright">© 2026 CleanCity</p>
        </div>
    </footer>

    <div id="toast"></div>

    <div class="modal-bg" id="confirmModal">
        <div class="modal">
            <div class="modal-icon" id="modalIcon"></div>
            <div class="modal-title" id="modalTitle">Confirm Report</div>
            <div class="modal-msg" id="modalMsg">Are you sure you want to submit this report?</div>
            <img class="modal-img" id="modalPreviewImg" src="" alt="">
            <div class="modal-loc" id="modalLoc"></div>
            <div class="modal-actions">
                <button class="btn-secondary" id="modalCancel">Cancel</button>
                <button class="btn-primary" id="modalConfirm">Yes, Submit</button>
            </div>
        </div>
    </div>

    <div class="modal-bg" id="profileModal">
        <div class="modal modal-lg">
            <button type="button" class="modal-x" onclick="closeModal('profileModal')"
                aria-label="Close">×</button>
            <div class="modal-title">My Profile</div>
            <div class="tabs">
                <button type="button" class="tab active" onclick="switchProfileTab('info', this)">Profile</button>
                <button type="button" class="tab" onclick="switchProfileTab('password', this)">Password</button>
            </div>
            <div class="tab-panel active" id="tabInfo">
                <form onsubmit="handleProfileSave(event)">
                    <div class="field"><label class="label">Full name</label><input type="text"
                            id="profileName" class="input" required></div>
                    <div class="field"><label class="label">Email</label><input type="email" id="profileEmail"
                            class="input" disabled></div>
                    <div class="field"><label class="label">Phone</label><input type="tel" id="profilePhone"
                            class="input" placeholder="09XX XXX XXXX"></div>
                    <button type="submit" class="btn-primary">Save changes</button>
                </form>
            </div>
            <div class="tab-panel" id="tabPassword">
                <form onsubmit="handlePasswordChange(event)">
                    <div class="field"><label class="label">Current password</label><input type="password"
                            id="currentPassword" class="input" required autocomplete="current-password"></div>
                    <div class="field"><label class="label">New password</label><input type="password"
                            id="newPassword" class="input" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="field"><label class="label">Confirm new password</label><input type="password"
                            id="confirmPassword" class="input" required minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-primary">Update password</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-bg" id="termsModal">
        <div class="modal modal-lg">
            <button type="button" class="modal-x" onclick="closeModal('termsModal')" aria-label="Close">×</button>
            <div class="modal-title">Terms & Privacy</div>
            <div class="tabs">
                <button type="button" class="tab active" onclick="switchTermsTab('tos', this)">Terms of
                    Service</button>
                <button type="button" class="tab" onclick="switchTermsTab('privacy', this)">Privacy
                    Policy</button>
            </div>
            <div class="tab-panel active terms" id="tabTos">
                <h4>1. Using this system</h4>
                <p>CleanCity provides residents a way to view collection schedules, track trucks, and report
                    waste-related issues.</p>
                <h4>2. Reports you submit</h4>
                <p>Reports (including photos and location) are shared with waste management staff solely to resolve the
                    reported issue.</p>
                <h4>3. Fair use</h4>
                <p>Do not submit false reports or misuse location sharing. Accounts found abusing the system may be
                    suspended.</p>
                <h4>4. Changes</h4>
                <p>These terms may be updated. Continued use after changes means you accept the updated terms.</p>
            </div>
            <div class="tab-panel terms" id="tabPrivacy">
                <h4>What we collect</h4>
                <p>Your name, contact details, and — only when you enable it — your live location while Share is on,
                    plus location and photo attached to reports.</p>
                <h4>How it’s used</h4>
                <p>Location data is used only to show nearby collection trucks and to help staff locate reported issues.
                    It is never sold.</p>
                <h4>Your control</h4>
                <p>Turn location sharing off anytime with the Share button. Update profile details from My Profile.</p>
                <h4>Retention</h4>
                <p>Report history and location logs are kept only as long as needed for operations and record-keeping.
                </p>
            </div>
        </div>
    </div>

    <button type="button" id="scrollTopBtn" aria-label="Back to top">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.4">
            <polyline points="18 15 12 9 6 15" />
        </svg>
    </button>

    <script src="{{ asset('js/request-guard.js') }}"></script>
    <script src="{{ asset('js/resident/dashboard.js') }}"></script>
    <script src="https://unpkg.com/pusher-js@8.4/dist/web/pusher.min.js"></script>
    <script src="https://unpkg.com/laravel-echo@1.16/dist/echo.iife.js"></script>
    <script>
        window.__reverbConfig = {
            enabled: {{ filter_var(env('REVERB_ENABLED', false), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false' }},
            key: "{{ config('broadcasting.connections.reverb.key') }}",
            host: "{{ env('REVERB_HOST', request()->getHost()) }}",
            port: {{ env('REVERB_PORT', 8080) }},
            scheme: "{{ env('REVERB_SCHEME', 'http') }}",
        };
    </script>
    <script src="{{ asset('js/resident/echo-bridge.js') }}"></script>
    <script src="{{ asset('js/chat-widget.js') }}"></script>
    <script>
        (function() {
            let user = {};
            try {
                user = JSON.parse(localStorage.getItem('user') || '{}');
            } catch (e) {}
            if (typeof initChatWidget === 'function') {
                initChatWidget({
                    role: 'resident',
                    token: localStorage.getItem('token'),
                    currentUserId: user && user.id,
                    apiBase: '/api',
                });
            }
        })();
    </script>
</body>

</html>
