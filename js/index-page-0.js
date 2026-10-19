import { finishLogin, refreshLoginChallenge, resetLoginSecurity, loginFeedback, loginCoolingDown } from './login-security.js?v=5';
import { escapeHTML, safeImage } from './security.js';

        import api, {
            request
        } from './api.js';

        // ── State ────────────────────────────────────────────────────────────────────
        let allRooms = [];
        let activeFilter = 'All';
        let selectedRoom = null;
        let selectedRating = 0;

        // ── Initialize ───────────────────────────────────────────────────────────────
        async function init() {
            setDefaultDates();
            await loadRooms();
            await loadReviews();
        }

        function setDefaultDates() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            const fmt = d => d.toISOString().split('T')[0];
            document.getElementById('search-checkin').value = fmt(today);
            document.getElementById('search-checkout').value = fmt(tomorrow);
            document.getElementById('res-checkin').value = fmt(today);
            document.getElementById('res-checkout').value = fmt(tomorrow);
        }

        // ── Load Rooms ───────────────────────────────────────────────────────────────
        async function loadRooms() {
            document.getElementById('rooms-skeleton').style.display = 'grid';
            const {
                data,
                error
            } = await api.from('rooms').select('*').order('room_number');
            document.getElementById('rooms-skeleton').style.display = 'none';
            if (error || !data) {
                showToast('Unable to load rooms from SQLite.', 'error');
                return;
            }
            allRooms = data;
            renderRooms(allRooms);
        }

        function renderFallbackRooms() {
            allRooms = [];
            showToast('Unable to load database records.', 'error');
            renderRooms(allRooms);
        }

        function renderRooms(rooms) {
            const grid = document.getElementById('rooms-grid');
            const empty = document.getElementById('rooms-empty');
            const count = document.getElementById('rooms-count');
            const available = rooms.filter(r => r.status === 'available').length;

            if (!rooms.length) {
                grid.innerHTML = '';
                grid.style.display = 'none';
                empty.style.display = 'block';
                count.textContent = 'No rooms match your search.';
                return;
            }
            empty.style.display = 'none';
            grid.style.display = 'grid';
            count.textContent = `${rooms.length} room${rooms.length !== 1 ? 's' : ''} found · ${available} available`;

            grid.innerHTML = rooms.map(room => roomCardHTML(room)).join('');
        }

        function roomCardHTML(room) {
            const isUnavail = room.status !== 'available';
            const badges = {
                available: '',
                reserved: '',
                occupied: '',
                maintenance: ''
            };
            const badgeMap = {
                available: 'badge-popular',
                reserved: 'badge-limited',
                occupied: 'badge-unavailable',
                maintenance: 'badge-unavailable'
            };
            const badgeLabel = {
                available: 'Most Popular',
                reserved: 'Reserved',
                occupied: 'Unavailable',
                maintenance: 'Maintenance'
            };
            const showBadge = room.status !== 'available' || room.is_featured;

            const amenities = Array.isArray(room.amenities) ? room.amenities : (room.amenities || []);
            const amenityPills = amenities.slice(0, 3).map(a => `<span class="pill">${escapeHTML(a)}</span>`).join('');

            const imgSrc = room.image_url || getDefaultRoomImage(room.room_type);
            const price = room.nightly_rate ? `$${Number(room.nightly_rate).toLocaleString()}` : '$—';
            const deposit = room.deposit_amount ? `$${Number(room.deposit_amount).toLocaleString()}` : '$—';

            return `
  <div class="room-card${isUnavail ? ' unavailable' : ''}">
    <div class="room-img-wrap">
      <img src="${escapeHTML(safeImage(imgSrc))}" alt="${escapeHTML(room.room_name)}" loading="lazy"
        data-evt-error="h39" />
      ${showBadge ? `<span class="room-badge ${badgeMap[room.status]}">${badgeLabel[room.status]}</span>` : ''}
      <span class="capacity-badge">👤 ${escapeHTML(room.capacity || 2)} guests</span>
    </div>
    <div class="room-body">
      <p class="room-type-num">${escapeHTML(room.room_type?.toUpperCase() || 'ROOM')} · #${escapeHTML(room.room_number)}</p>
      <h3 class="room-name">${escapeHTML(room.room_name)}</h3>
      <p class="room-price">${price}<span>/night</span></p>
      <div class="amenity-pills">${amenityPills || '<span class="pill">Premium Room</span>'}</div>
      <div class="room-footer">
        <div>
          <p class="deposit-label">Deposit</p>
          <p class="deposit-amount">${deposit}</p>
        </div>
        <button class="btn-reserve" data-call="openReserveModal" data-args="${escapeHTML(JSON.stringify([room.id]))}"
          ${isUnavail ? 'disabled' : ''}>
          ${isUnavail ? 'Unavailable' : 'Reserve →'}
        </button>
      </div>
    </div>
  </div>`;
        }

        // ── Filter logic ──────────────────────────────────────────────────────────────
        window.filterRooms = function() {
            const type = document.getElementById('search-type').value;
            const checkin = document.getElementById('search-checkin').value;
            const checkout = document.getElementById('search-checkout').value;
            activeFilter = type || 'All';
            updateChips(activeFilter);
            let filtered = allRooms;
            if (type) filtered = filtered.filter(r => r.room_type === type);
            renderRooms(filtered);
        };

        document.getElementById('filter-chips').addEventListener('click', (e) => {
            const chip = e.target.closest('.chip');
            if (!chip) return;
            activeFilter = chip.dataset.filter;
            updateChips(activeFilter);
            const filtered = activeFilter === 'All' ? allRooms : allRooms.filter(r => r.room_type === activeFilter);
            renderRooms(filtered);
        });

        function updateChips(active) {
            document.querySelectorAll('.chip').forEach(c => {
                c.classList.toggle('active', c.dataset.filter === active);
            });
        }

        // ── Reserve Modal ─────────────────────────────────────────────────────────────
        window.openReserveModal = function(roomId) {
            selectedRoom = allRooms.find(r => r.id == roomId || String(r.id) === String(roomId));
            if (!selectedRoom) return;

            document.getElementById('modal-room-label').textContent = `Room #${selectedRoom.room_number}`;
            document.getElementById('modal-room-img').src = safeImage(selectedRoom.image_url || getDefaultRoomImage(selectedRoom.room_type));
            document.getElementById('modal-room-type').textContent = `${selectedRoom.room_type?.toUpperCase()} · #${selectedRoom.room_number}`;
            document.getElementById('modal-room-name').textContent = selectedRoom.room_name;
            document.getElementById('modal-room-rate').textContent = `$${Number(selectedRoom.nightly_rate).toLocaleString()}/night`;
            document.getElementById('modal-room-deposit').textContent = `$${Number(selectedRoom.deposit_amount).toLocaleString()}`;
            document.getElementById('res-room-id').value = selectedRoom.id;

            document.getElementById('reserve-form-wrap').style.display = 'block';
            document.getElementById('booking-success').style.display = 'none';
            document.getElementById('reserve-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        };

        window.closeReserveModal = function() {
            document.getElementById('reserve-modal').classList.remove('open');
            document.body.style.overflow = '';
            document.getElementById('reserve-form').reset();
        };

        document.getElementById('reserve-modal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeReserveModal();
        });

        window.previewReceipt = function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = document.getElementById('receipt-preview');
                img.src = ev.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);
        };

        // ── Submit Reservation ────────────────────────────────────────────────────────
        window.submitReservation = async function(e) {
            e.preventDefault();
            const btn = document.getElementById('reserve-submit-btn');

            const name = document.getElementById('res-name').value.trim();
            const email = document.getElementById('res-email').value.trim();
            const phone = document.getElementById('res-phone').value.trim();
            const guests = document.getElementById('res-guests').value;
            const checkin = document.getElementById('res-checkin').value;
            const checkout = document.getElementById('res-checkout').value;
            const requests = document.getElementById('res-requests').value.trim();
            const roomId = document.getElementById('res-room-id').value;

            if (!name || !email || !checkin || !checkout || !roomId) {
                showToast('Please fill in all required fields.', 'error');
                return;
            }

            const bookingRef = `INP-${new Date().getFullYear()}-${Date.now().toString().slice(-6)}`;
            const guestId = `G-${String(Math.floor(Math.random() * 90000) + 10000)}`;

            if (checkout <= checkin) {
                showToast('Check-out must follow check-in.', 'error');
                return;
            }
            btn.disabled = true;
            const receiptImage = document.getElementById('receipt-preview');
            const {
                data,
                error
            } = await request({
                action: 'book',
                reservation: {
                    guest_name: name,
                    guest_email: email,
                    guest_phone: phone,
                    room_id: roomId,
                    check_in: checkin,
                    check_out: checkout,
                    guest_count: parseInt(guests) || 1,
                    special_requests: requests,
                },
                receipt: receiptImage?.src.startsWith('data:') ? receiptImage.src : null
            });
            btn.disabled = false;
            if (error) {
                showToast(error.message, 'error');
                return;
            }
            document.getElementById('booking-ref-display').textContent = data.booking_ref;
            document.getElementById('reserve-form-wrap').style.display = 'none';
            document.getElementById('booking-success').style.display = 'block';
            await loadRooms();
        };

        // ── Load Reviews ──────────────────────────────────────────────────────────────
        async function loadReviews() {
            const {
                data
            } = await api.from('reviews').select('*')
                .eq('approved', true).order('created_at', {
                    ascending: false
                }).limit(3);
            if (!data || !data.length) return;
            const grid = document.getElementById('reviews-grid');
            grid.innerHTML = data.map(r => `
    <div class="review-card">
      <div class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div>
      <p class="review-text">"${escapeHTML(r.review_text)}"</p>
      <p class="review-author">${escapeHTML(r.guest_name)}</p>
      <p class="review-date">${new Date(r.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}</p>
    </div>`).join('');
        }

        // ── Submit Review ─────────────────────────────────────────────────────────────
        window.submitReview = async function(e) {
            e.preventDefault();
            if (!selectedRating) {
                showToast('Please select a star rating.', 'warning');
                return;
            }
            const name = document.getElementById('review-name').value.trim();
            const text = document.getElementById('review-text').value.trim();
            const btn = document.getElementById('review-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting…';

            const {
                error
            } = await api.from('reviews').insert({
                guest_name: name,
                rating: selectedRating,
                review_text: text,
                approved: false,
            });

            if (error) {
                showToast('Could not submit review.', 'error');
            } else {
                showToast('Thank you for your review! It will appear once approved.', 'success');
                e.target.reset();
                setRating(0);
            }
            btn.disabled = false;
            btn.textContent = 'Submit Review';
        };

        // ── Star rating ───────────────────────────────────────────────────────────────
        window.setRating = function(val) {
            selectedRating = val;
            document.querySelectorAll('.star-btn').forEach((s, i) => {
                s.style.color = i < val ? '#D4AF37' : '#8B9198';
            });
        };

        // ── Contact form ──────────────────────────────────────────────────────────────
        window.submitContact = function(e) {
            e.preventDefault();
            showToast('Message sent! We\'ll be in touch shortly.', 'success');
            e.target.reset();
        };

        // ── Mobile menu ───────────────────────────────────────────────────────────────
        window.toggleMobileMenu = function() {
            const menu = document.getElementById('mobile-menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        };
        window.closeMobileMenu = function() {
            document.getElementById('mobile-menu').style.display = 'none';
        };

        // ── Helpers ───────────────────────────────────────────────────────────────────
        function generateBookingRef() {
            return `INP-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 9000) + 1000)}`;
        }

        function generateGuestId() {
            return `G-${String(Math.floor(Math.random() * 9000) + 1000)}`;
        }

        function getDefaultRoomImage(type) {
            const imgs = {
                Suite: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
                Penthouse: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=80',
                Loft: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=600&q=80',
                Classic: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&q=80',
                Studio: 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=600&q=80',
            };
            return imgs[type] || imgs.Suite;
        }

        function getFallbackRooms() {
            return [{
                    id: 'r1',
                    room_number: '101',
                    room_name: 'Grand Deluxe Suite',
                    room_type: 'Suite',
                    capacity: 2,
                    nightly_rate: 420,
                    deposit_amount: 150,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
                    amenities: ['King Bed', 'Ocean View', 'Butler Service'],
                    is_featured: true
                },
                {
                    id: 'r2',
                    room_number: '102',
                    room_name: 'Classic Room',
                    room_type: 'Classic',
                    capacity: 2,
                    nightly_rate: 180,
                    deposit_amount: 60,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&q=80',
                    amenities: ['Queen Bed', 'City View', 'Free Wi-Fi']
                },
                {
                    id: 'r3',
                    room_number: '103',
                    room_name: 'Classic Room',
                    room_type: 'Classic',
                    capacity: 2,
                    nightly_rate: 180,
                    deposit_amount: 60,
                    status: 'occupied',
                    image_url: 'https://images.unsplash.com/photo-1566195992011-5f6b21e539aa?w=600&q=80',
                    amenities: ['Twin Beds', 'Garden View']
                },
                {
                    id: 'r4',
                    room_number: '115',
                    room_name: 'Classic Room',
                    room_type: 'Classic',
                    capacity: 2,
                    nightly_rate: 195,
                    deposit_amount: 65,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&q=80',
                    amenities: ['King Bed', 'Pool View']
                },
                {
                    id: 'r5',
                    room_number: '201',
                    room_name: 'Garden Loft',
                    room_type: 'Loft',
                    capacity: 3,
                    nightly_rate: 320,
                    deposit_amount: 110,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=600&q=80',
                    amenities: ['King Bed', 'Garden Access', 'Terrace']
                },
                {
                    id: 'r6',
                    room_number: '202',
                    room_name: 'Heritage Loft Room',
                    room_type: 'Loft',
                    capacity: 2,
                    nightly_rate: 280,
                    deposit_amount: 95,
                    status: 'reserved',
                    image_url: 'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?w=600&q=80',
                    amenities: ['Queen Bed', 'City View', 'Workspace']
                },
                {
                    id: 'r7',
                    room_number: '210',
                    room_name: 'Botanical Suite',
                    room_type: 'Suite',
                    capacity: 2,
                    nightly_rate: 380,
                    deposit_amount: 130,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=80',
                    amenities: ['King Bed', 'Botanical Garden View', 'Spa Bath']
                },
                {
                    id: 'r8',
                    room_number: '211',
                    room_name: 'Deluxe Suite',
                    room_type: 'Suite',
                    capacity: 3,
                    nightly_rate: 450,
                    deposit_amount: 160,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
                    amenities: ['King Bed', 'Sea View', 'Private Lounge']
                },
                {
                    id: 'r9',
                    room_number: '218',
                    room_name: 'Signature Suite',
                    room_type: 'Suite',
                    capacity: 4,
                    nightly_rate: 620,
                    deposit_amount: 200,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?w=600&q=80',
                    amenities: ['Master Bed', 'Ocean Panorama', 'Butler Service'],
                    is_featured: true
                },
                {
                    id: 'r10',
                    room_number: '303',
                    room_name: 'Atelier Penthouse',
                    room_type: 'Penthouse',
                    capacity: 4,
                    nightly_rate: 980,
                    deposit_amount: 300,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=600&q=80',
                    amenities: ['2 Bedrooms', 'Panoramic View', 'Private Pool'],
                    is_featured: true
                },
                {
                    id: 'r11',
                    room_number: '401',
                    room_name: 'Studio',
                    room_type: 'Studio',
                    capacity: 1,
                    nightly_rate: 140,
                    deposit_amount: 50,
                    status: 'available',
                    image_url: 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=600&q=80',
                    amenities: ['Double Bed', 'Compact Kitchen']
                },
                {
                    id: 'r12',
                    room_number: '405',
                    room_name: 'Library Studio',
                    room_type: 'Studio',
                    capacity: 2,
                    nightly_rate: 165,
                    deposit_amount: 55,
                    status: 'maintenance',
                    image_url: 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=600&q=80',
                    amenities: ['Queen Bed', 'Reading Nook']
                },
            ];
        }

        function showToast(msg, type = 'info') {
            const el = document.createElement('div');
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            el.className = `toast toast-${type}`;
            el.innerHTML = `<span style="font-size:16px">${icons[type] || 'ℹ'}</span><span>${escapeHTML(msg)}</span>`;
            document.body.appendChild(el);
            setTimeout(() => {
                el.style.opacity = '0';
                el.style.transform = 'translateX(60px)';
                el.style.transition = 'all 0.3s';
                setTimeout(() => el.remove(), 300);
            }, 3500);
        }

        // ══════════════════════════════════════════════════════════════
        // LOGIN MODAL
        // ══════════════════════════════════════════════════════════════

        window.openLoginModal = async function() {
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (stored) {
                try {
                    const u = JSON.parse(stored);
                    window.location.href = u.role === 'admin' ? 'admin.php' : 'front-desk.php';
                    return;
                } catch (_) {
                    await api.logout();
                }
            }
            document.getElementById('login-modal-overlay').classList.add('open');
            await refreshLoginChallenge();
            document.body.style.overflow = 'hidden';
            setTimeout(() => document.getElementById('lm-email').focus(), 120);
        };

        window.closeLoginModal = function() {
            document.getElementById('login-modal-overlay').classList.remove('open');
            document.body.style.overflow = '';
            document.getElementById('lm-form').reset();
            document.getElementById('lm-error').style.display = 'none';
            document.querySelectorAll('.lm-input').forEach(i => i.classList.remove('lm-error'));
            const btn = document.getElementById('lm-submit-btn');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Sign In →';
            }
        };

        window.handleLoginOverlayClick = function(e) {
            if (e.target === document.getElementById('login-modal-overlay')) closeLoginModal();
        };

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeLoginModal();
        });

        let loginSubmitting = false;
        window.handleLoginModal = async function(e) {
            e.preventDefault();
            if (loginSubmitting || loginCoolingDown()) return;
            const email = document.getElementById('lm-email').value.trim().toLowerCase();
            const password = document.getElementById('lm-password').value;
            const btn = document.getElementById('lm-submit-btn');

            document.getElementById('lm-error').style.display = 'none';
            document.querySelectorAll('.lm-input').forEach(i => i.classList.remove('lm-error'));

            if (!email || !password) {
                showLmError('Please enter your email and password.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="lm-spinner"></span>';
            loginSubmitting = true;

            const captchaToken = await refreshLoginChallenge(false);
            const result = await finishLogin(await api.login(email, password, captchaToken));
            const { data: account, error } = result;
            resetLoginSecurity();
            await refreshLoginChallenge();
            if (error || !account) {
                showLmError(error?.message || 'Sign in failed.');
                loginSubmitting = false;
                btn.disabled = false;
                btn.textContent = 'Sign In →';
                loginFeedback(error || {});
                return;
            }
            loginSubmitting = false;
            window.location.href = account.role === 'admin' ? 'admin.php' : 'front-desk.php';
        };

        function showLmError(msg) {
            document.getElementById('lm-error-text').textContent = msg;
            document.getElementById('lm-error').style.display = 'flex';
        }

        window.fillLmDemo = function(email, password) {
            document.getElementById('lm-email').value = email;
            document.getElementById('lm-password').value = password;
            document.getElementById('lm-error').style.display = 'none';
        };

        window.toggleLmPw = function() {
            const input = document.getElementById('lm-password');
            const btn = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        };

        // On page load: auto-open if ?login=1, show Portal button if already logged in
        (async () => {
            if (new URLSearchParams(window.location.search).get('login') === '1') {
                history.replaceState({}, '', window.location.pathname);
                setTimeout(() => window.openLoginModal(), 300);
            }
            const sessionProfile = await api.session();
            const stored = sessionProfile ? JSON.stringify(sessionProfile) : null;
            if (stored) {
                try {
                    const u = JSON.parse(stored);
                    document.querySelectorAll('.btn-login').forEach(btn => {
                        btn.textContent = '→ Portal';
                        btn.onclick = () => {
                            window.location.href = u.role === 'admin' ? 'admin.php' : 'front-desk.php';
                        };
                    });
                } catch (_) {
                    await api.logout();
                }
            }
        })();

        init();
    