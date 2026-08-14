// ==========================================================
// লাইভ লোকেশন ও ডেলিভারি ট্র্যাকিং (OpenStreetMap + Leaflet.js — ফ্রি, কোনো API Key লাগে না)
// ==========================================================

function loadLeaflet(callback, onerror) {
    if (window.L) { callback(); return; }
    if (window.__leafletLoading) {
        window.__leafletCallbacks = window.__leafletCallbacks || [];
        window.__leafletErrorCallbacks = window.__leafletErrorCallbacks || [];
        window.__leafletCallbacks.push(callback);
        if (onerror) window.__leafletErrorCallbacks.push(onerror);
        return;
    }
    window.__leafletLoading = true;
    window.__leafletCallbacks = [callback];
    window.__leafletErrorCallbacks = onerror ? [onerror] : [];

    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);

    // CDN ধীর হলে বা ব্লক থাকলে যেন চিরকাল "লোড হচ্ছে" অবস্থায় আটকে না থাকে —
    // ১০ সেকেন্ডের মধ্যে লোড না হলে ব্যর্থ ধরে নিয়ে error callback চালানো হয়।
    const timeoutId = setTimeout(function () {
        if (window.L) return; // ততক্ষণে লোড হয়ে গেলে কিছু করার দরকার নেই
        window.__leafletLoading = false;
        (window.__leafletErrorCallbacks || []).forEach(function (cb) { cb(); });
        window.__leafletErrorCallbacks = [];
    }, 10000);

    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = function () {
        clearTimeout(timeoutId);
        (window.__leafletCallbacks || []).forEach(function (cb) { cb(); });
        window.__leafletCallbacks = [];
        window.__leafletErrorCallbacks = [];
    };
    script.onerror = function () {
        clearTimeout(timeoutId);
        window.__leafletLoading = false;
        (window.__leafletErrorCallbacks || []).forEach(function (cb) { cb(); });
        window.__leafletCallbacks = [];
        window.__leafletErrorCallbacks = [];
    };
    document.head.appendChild(script);
}

// ---------- Nominatim রিকোয়েস্ট (রেট-লিমিট মেনে, সেকেন্ডে একবারের বেশি নয়) ----------
let __lastGeocodeCall = 0;
const __geocodeQueue = [];
let __geocodeProcessing = false;

function __buildNominatimUrl(job) {
    if (job.query) {
        return `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(job.query)}&limit=1&accept-language=bn`;
    }
    return `https://nominatim.openstreetmap.org/reverse?format=json&lat=${job.lat}&lon=${job.lng}&accept-language=bn&addressdetails=1&zoom=18`;
}

function __processGeocodeQueue() {
    if (__geocodeProcessing) return;
    __geocodeProcessing = true;
    const step = () => {
        const job = __geocodeQueue.shift();
        if (!job) { __geocodeProcessing = false; return; }
        const wait = Math.max(0, 1100 - (Date.now() - __lastGeocodeCall));
        setTimeout(async () => {
            __lastGeocodeCall = Date.now();
            try {
                const res = await fetch(__buildNominatimUrl(job));
                const data = await res.json();
                if (job.query) {
                    job.resolve(Array.isArray(data) && data.length ? data[0] : null);
                } else {
                    job.resolve(data && data.display_name ? data : null);
                }
            } catch (e) {
                job.resolve(null);
            }
            step();
        }, wait);
    };
    step();
}

function __fetchNominatim(lat, lng) {
    return new Promise((resolve) => {
        __geocodeQueue.push({ lat, lng, resolve });
        __processGeocodeQueue();
    });
}

function __searchNominatim(query) {
    return new Promise((resolve) => {
        __geocodeQueue.push({ query, resolve });
        __processGeocodeQueue();
    });
}

// লেখা ঠিকানা থেকে আনুমানিক অবস্থান (lat/lng) — ম্যাপে প্রিভিউ দেখানোর জন্য
async function forwardGeocode(query) {
    const result = await __searchNominatim(query);
    if (!result) return null;
    return { lat: parseFloat(result.lat), lng: parseFloat(result.lon), display_name: result.display_name };
}

// পূর্ণ ঠিকানা (অর্ডারের সময় ঠিকানা ফিল্ডে বসানোর জন্য)
async function reverseGeocode(lat, lng) {
    const data = await __fetchNominatim(lat, lng);
    return data && data.display_name ? data.display_name : null;
}

// সংক্ষিপ্ত কিন্তু নির্দিষ্ট (specific) জায়গার নাম — লাইভ ট্র্যাকিং-এ দেখানোর জন্য
// যেমন: "রোড ৭, ধানমন্ডি, ঢাকা" — পুরো display_name এর বদলে সবচেয়ে প্রাসঙ্গিক অংশগুলো বাছাই করে
async function reverseGeocodeShort(lat, lng) {
    const data = await __fetchNominatim(lat, lng);
    if (!data) return null;
    const addr = data.address || {};
    const primary = addr.road || addr.neighbourhood || addr.residential || addr.quarter || addr.hamlet || addr.village || addr.suburb;
    const area = addr.suburb || addr.city_district || addr.municipality || addr.town || addr.upazila;
    const city = addr.city || addr.town || addr.district || addr.county;
    const parts = [];
    if (primary) parts.push(primary);
    if (area && area !== primary) parts.push(area);
    if (city && city !== area && city !== primary) parts.push(city);
    if (parts.length === 0) return data.display_name || null;
    return parts.slice(0, 3).join('، ');
}

// ---------- টাইপ করা ঠিকানা ম্যাপে প্রিভিউ দেখানো (শুধু দেখানোর জন্য, lat/lng সেভ করে না) ----------
function initAddressMapPreview(opts) {
    const input = document.getElementById(opts.addressInputId);
    const mapBox = document.getElementById(opts.mapContainerId);
    const statusEl = opts.statusId ? document.getElementById(opts.statusId) : null;
    if (!input || !mapBox) return;

    let map, marker, debounceTimer, lastQuery = '';

    function setStatus(text) { if (statusEl) statusEl.innerText = text; }

    function showOnMap(lat, lng, label) {
        mapBox.classList.add('active');
        loadLeaflet(function () {
            if (!map) {
                map = L.map(opts.mapContainerId).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                marker = L.marker([lat, lng]).addTo(map);
                setTimeout(function () { map.invalidateSize(); }, 200);
            } else {
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
            }
            if (label) marker.bindPopup(label).openPopup();
            setStatus('✅ ম্যাপে আনুমানিক জায়গাটি দেখানো হয়েছে — ঠিক আছে কিনা মিলিয়ে নিন।');
        }, function () {
            mapBox.classList.remove('active');
            setStatus('⚠️ ম্যাপ লোড করা যায়নি (ইন্টারনেট সংযোগ চেক করুন), তবে আপনার লেখা ঠিকানাই সংরক্ষিত হবে।');
        });
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 4) {
            setStatus('');
            return;
        }
        debounceTimer = setTimeout(async function () {
            if (q === lastQuery) return;
            lastQuery = q;
            setStatus('🔎 ম্যাপে খোঁজা হচ্ছে...');
            const result = await forwardGeocode(q);
            if (q !== input.value.trim()) return; // এর মধ্যে আরও লেখা হয়ে গেলে পুরনো ফলাফল বাদ
            if (result) {
                showOnMap(result.lat, result.lng, '📍 আপনার লেখা ঠিকানা (আনুমানিক)');
            } else {
                setStatus('⚠️ এই ঠিকানাটি ম্যাপে খুঁজে পাওয়া যায়নি, তবে আপনার লেখা ঠিকানাই সংরক্ষিত হবে।');
            }
        }, 900);
    });
}

// ---------- অর্ডারের সময় "বর্তমান অবস্থান ব্যবহার করুন" ----------
function initAddressLocationPicker(opts) {
    const btn = document.getElementById(opts.buttonId);
    const statusEl = document.getElementById(opts.statusId);
    const addressInput = document.getElementById(opts.addressInputId);
    const latInput = document.getElementById(opts.latInputId);
    const lngInput = document.getElementById(opts.lngInputId);
    const mapBox = document.getElementById(opts.mapContainerId);
    if (!btn) return;

    let map, marker;

    function setStatus(text) { if (statusEl) statusEl.innerText = text; }

    function showMap(lat, lng) {
        mapBox.classList.add('active');
        loadLeaflet(function () {
            if (!map) {
                map = L.map(opts.mapContainerId).setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', async function () {
                    const pos = marker.getLatLng();
                    latInput.value = pos.lat.toFixed(7);
                    lngInput.value = pos.lng.toFixed(7);
                    setStatus('অবস্থান আপডেট হচ্ছে...');
                    const addr = await reverseGeocode(pos.lat, pos.lng);
                    if (addr && addressInput) addressInput.value = addr;
                    setStatus('📍 মার্কার সরিয়ে সঠিক ঠিকানা ঠিক করে নিন।');
                });
                setTimeout(function () { map.invalidateSize(); }, 200);
            } else {
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
            }
            setStatus('📍 মার্কার সরিয়ে (drag করে) সঠিক জায়গায় বসাতে পারেন।');
        }, function () {
            mapBox.classList.remove('active');
            setStatus('⚠️ ম্যাপ লোড করা যায়নি (ইন্টারনেট সংযোগ চেক করুন), তবে আপনার অবস্থান (lat/lng) সংরক্ষিত হয়ে গেছে।');
        });
    }

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            setStatus('আপনার ব্রাউজার লোকেশন সাপোর্ট করে না।');
            return;
        }
        btn.disabled = true;
        setStatus('📡 অবস্থান শনাক্ত করা হচ্ছে...');
        navigator.geolocation.getCurrentPosition(async function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            setStatus('ঠিকানা খোঁজা হচ্ছে...');
            const addr = await reverseGeocode(lat, lng);
            if (addr && addressInput) {
                addressInput.value = addr;
            }
            showMap(lat, lng);
            btn.disabled = false;
        }, function (err) {
            setStatus('অবস্থান পাওয়া যায়নি। ব্রাউজারে লোকেশন পারমিশন দিন।');
            btn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 15000 });
    });
}

// ---------- বিক্রেতা (কৃষক) পাশ থেকে লাইভ লোকেশন শেয়ার করা ----------
function initLocationSharing(opts) {
    const btn = document.getElementById(opts.buttonId);
    const statusEl = document.getElementById(opts.statusId);
    if (!btn) return;

    let watchId = null;

    function setStatus(text) { if (statusEl) statusEl.innerText = text; }

    function sendLocation(lat, lng) {
        fetch('update_order_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order_id=${encodeURIComponent(opts.orderId)}&lat=${lat}&lng=${lng}`
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && data.success) {
                setStatus('✅ শেষ পাঠানো: ' + new Date().toLocaleTimeString('bn-BD'));
            } else {
                setStatus('❌ লোকেশন পাঠাতে সমস্যা হয়েছে।');
            }
        }).catch(function () {
            setStatus('❌ নেটওয়ার্ক সমস্যা, আবার চেষ্টা হবে।');
        });
    }

    btn.addEventListener('click', function () {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
            btn.innerText = '📍 লাইভ লোকেশন শেয়ার করুন';
            btn.classList.remove('btn-danger');
            setStatus('লাইভ ট্র্যাকিং বন্ধ আছে।');
            return;
        }
        if (!navigator.geolocation) {
            setStatus('আপনার ব্রাউজার লোকেশন সাপোর্ট করে না।');
            return;
        }
        setStatus('📡 চালু হচ্ছে...');
        watchId = navigator.geolocation.watchPosition(function (pos) {
            sendLocation(pos.coords.latitude, pos.coords.longitude);
        }, function () {
            setStatus('অবস্থান পাওয়া যায়নি। লোকেশন পারমিশন চেক করুন।');
        }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 });
        btn.innerText = '⏹️ লাইভ ট্র্যাকিং বন্ধ করুন';
        btn.classList.add('btn-danger');
        setStatus('লাইভ ট্র্যাকিং চালু হয়েছে...');
    });
}

// ---------- ক্রেতা পাশ থেকে ডেলিভারি ট্র্যাকিং ম্যাপ দেখা ----------
function initOrderTrackingMap(opts) {
    const mapBox = document.getElementById(opts.mapContainerId);
    const updatedEl = document.getElementById(opts.updatedAtId);
    const nameEl = opts.locationNameId ? document.getElementById(opts.locationNameId) : null;
    if (!mapBox) return;

    let map, destMarker, liveMarker;
    let pollTimer = null;
    let lastGeoLat = null, lastGeoLng = null, geoInFlight = false;

    function ensureMap(destLat, destLng) {
        mapBox.classList.add('active');
        loadLeaflet(function () {
            if (!map) {
                map = L.map(opts.mapContainerId).setView([destLat, destLng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                if (destLat && destLng) {
                    destMarker = L.marker([destLat, destLng]).addTo(map).bindPopup('🏠 ডেলিভারি ঠিকানা');
                }
                setTimeout(function () { map.invalidateSize(); }, 200);
            }
        }, function () {
            if (updatedEl) updatedEl.innerText = '⚠️ ম্যাপ লোড করা যায়নি (ইন্টারনেট সংযোগ চেক করুন)।';
        });
    }

    // কারিয়ার নড়াচড়া করলেই বারবার Nominatim-কে কল না করে, কিছুটা দূরত্ব সরলে তবেই নতুন
    // জায়গার নাম আনা হয় — নাহলে অকারণে বারবার একই এলাকার নাম রিফ্রেশ হতে থাকবে।
    function updateLocationName(lat, lng) {
        if (!nameEl) return;
        const movedEnough = lastGeoLat === null ||
            Math.abs(lat - lastGeoLat) > 0.0006 || Math.abs(lng - lastGeoLng) > 0.0006;
        if (!movedEnough || geoInFlight) return;
        geoInFlight = true;
        lastGeoLat = lat;
        lastGeoLng = lng;
        if (!nameEl.dataset.hasName) nameEl.innerText = '📍 জায়গার নাম খোঁজা হচ্ছে...';
        reverseGeocodeShort(lat, lng).then(function (name) {
            geoInFlight = false;
            if (name) {
                nameEl.dataset.hasName = '1';
                nameEl.innerText = '📍 ' + name;
                if (liveMarker) liveMarker.bindPopup('🚚 বর্তমান অবস্থান<br>' + name);
            }
        });
    }

    async function poll() {
        try {
            const res = await fetch(opts.pollUrl);
            const data = await res.json();
            if (!data || !data.success) return;

            const destLat = parseFloat(data.delivery_lat);
            const destLng = parseFloat(data.delivery_lng);
            if (!isNaN(destLat) && !isNaN(destLng)) {
                ensureMap(destLat, destLng);
            }

            const curLat = parseFloat(data.current_lat);
            const curLng = parseFloat(data.current_lng);
            if (!isNaN(curLat) && !isNaN(curLng)) {
                if (!map) ensureMap(curLat, curLng);
                loadLeaflet(function () {
                    if (!map) return;
                    if (!liveMarker) {
                        const icon = L.divIcon({ className: 'live-truck-icon', html: '<div class="live-truck-pulse"></div><div class="live-truck-emoji">🚚</div>', iconSize: [34, 34] });
                        liveMarker = L.marker([curLat, curLng], { icon: icon, zIndexOffset: 1000 }).addTo(map).bindPopup('🚚 বর্তমান অবস্থান');
                    } else {
                        liveMarker.setLatLng([curLat, curLng]);
                    }
                    const bounds = [];
                    if (destMarker) bounds.push(destMarker.getLatLng());
                    bounds.push(liveMarker.getLatLng());
                    if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40] });
                });
                updateLocationName(curLat, curLng);
            } else if (nameEl && !nameEl.dataset.hasName) {
                nameEl.innerText = '📍 বিক্রেতা এখনো লাইভ লোকেশন শেয়ার করেননি।';
            }

            if (updatedEl) {
                updatedEl.innerText = data.location_updated_at
                    ? '📶 সর্বশেষ অবস্থান আপডেট: ' + data.location_updated_at
                    : 'বিক্রেতা এখনো লাইভ লোকেশন শেয়ার করেননি।';
            }

            if (opts.onStatus) opts.onStatus(data.status);
        } catch (e) {
            // পরের পোলে আবার চেষ্টা হবে
        }
    }

    poll();
    pollTimer = setInterval(poll, 15000);
    window.addEventListener('beforeunload', function () {
        if (pollTimer) clearInterval(pollTimer);
    });

    // ম্যাপ-বক্স শুরুতে display:none থাকা কোনো প্যানেলের ভেতরে থাকলে Leaflet
    // 0 সাইজের container-এ map বানায় ও ভাঙা/ফাঁকা দেখায়। প্যানেলটা visible
    // হওয়ার সময় বাইরে থেকে এই ফাংশন কল করলে map সঠিক সাইজে রিফ্রেশ হয়ে যাবে।
    return {
        refresh: function () {
            if (map) {
                setTimeout(function () { map.invalidateSize(); }, 50);
            }
        }
    };
}
