-- ============================================================
-- InnPulse Boutique Hotel — Seed Data
-- Educational / Demonstration Environment
-- ⚠ SYNTHETIC DATA ONLY — No real customer information
-- ============================================================

-- ── ROOMS ────────────────────────────────────────────────────────────────────
INSERT INTO public.rooms (room_number, room_name, room_type, description, capacity, nightly_rate, deposit_amount, status, image_url, amenities, is_featured)
VALUES
  ('101', 'Grand Deluxe Suite',     'Suite',     'Our flagship suite featuring panoramic ocean views, a private lounge, and dedicated butler service. Decorated with hand-selected artwork and bespoke furnishings.',
   2, 420.00, 150.00, 'occupied',
   'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80',
   '["King Bed","Ocean View","Butler Service","Spa Bath","Private Terrace"]', TRUE),

  ('102', 'Classic Room',           'Classic',   'An elegantly appointed classic room with city views. Understated luxury with all essential amenities.',
   2, 180.00, 60.00, 'available',
   'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80',
   '["Queen Bed","City View","Free Wi-Fi","Mini Bar"]', FALSE),

  ('103', 'Classic Room',           'Classic',   'Twin-bedded classic room perfect for colleagues or companions, with lush garden views.',
   2, 180.00, 60.00, 'occupied',
   'https://images.unsplash.com/photo-1566195992011-5f6b21e539aa?w=800&q=80',
   '["Twin Beds","Garden View","Desk","Free Wi-Fi"]', FALSE),

  ('115', 'Classic Room',           'Classic',   'Refreshed classic accommodation with plush bedding, modern bathroom, and serene pool views.',
   2, 195.00, 65.00, 'available',
   'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&q=80',
   '["King Bed","Pool View","Bathrobe","Nespresso Machine"]', FALSE),

  ('201', 'Garden Loft',            'Loft',      'A spacious bi-level loft with private terrace overlooking the hotel garden. Featuring exposed brick accents and local botanical artwork.',
   3, 320.00, 110.00, 'reserved',
   'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&q=80',
   '["King Bed","Garden Access","Terrace","Living Area","Soaking Tub"]', TRUE),

  ('202', 'Heritage Loft Room',     'Loft',      'A characterful loft featuring restored heritage architectural details, warm tones, and a fully equipped workspace.',
   2, 280.00, 95.00, 'reserved',
   'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?w=800&q=80',
   '["Queen Bed","City View","Heritage Decor","Workspace","Complimentary Minibar"]', FALSE),

  ('210', 'Botanical Suite',        'Suite',     'Inspired by the surrounding gardens, this suite features living plant walls, natural stone surfaces, and a therapeutic spa bath.',
   2, 380.00, 130.00, 'available',
   'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80',
   '["King Bed","Botanical Garden View","Spa Bath","Living Wall","Butler Service"]', FALSE),

  ('211', 'Deluxe Suite',           'Suite',     'A spacious deluxe suite with expansive sea views, a separate living area, private lounge, and premium in-suite amenities.',
   3, 450.00, 160.00, 'available',
   'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80',
   '["King Bed","Sea View","Separate Living Room","Private Lounge","Butler Service"]', FALSE),

  ('218', 'Signature Suite',        'Suite',     'The pinnacle of in-hotel luxury. Four-person occupancy with master bedroom, dedicated concierge floor access, and sky-view terrace.',
   4, 620.00, 200.00, 'occupied',
   'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?w=800&q=80',
   '["Master Bed","Ocean Panorama","Butler Service","Concierge Floor Access","In-Suite Dining"]', TRUE),

  ('303', 'Atelier Penthouse',      'Penthouse',  'A full-floor penthouse experience with two bedrooms, private rooftop pool, chef''s kitchen, and 360° city panorama.',
   4, 980.00, 300.00, 'available',
   'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&q=80',
   '["2 Bedrooms","Panoramic View","Private Rooftop Pool","Chef Kitchen","Personal Butler"]', TRUE),

  ('401', 'Studio',                 'Studio',    'A well-designed compact studio ideal for solo travellers or short business stays. Efficient, elegant, and centrally located.',
   1, 140.00, 50.00, 'available',
   'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=800&q=80',
   '["Double Bed","City View","Compact Kitchenette","Desk"]', FALSE),

  ('405', 'Library Studio',         'Studio',    'A unique studio with an in-room curated book collection, reading nook by the window, and warm library-inspired interiors.',
   2, 165.00, 55.00, 'maintenance',
   'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=800&q=80',
   '["Queen Bed","Reading Nook","Curated Library","Desk","Garden View"]', FALSE),

  ('410', 'Penthouse',              'Penthouse',  'Our classic penthouse level accommodation. Floor-to-ceiling windows, private pool access, and extraordinary views from all angles.',
   4, 1100.00, 350.00, 'available',
   'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&q=80',
   '["2 Bedrooms","City Panorama","Private Pool Access","Gourmet Kitchen","Premium Butler"]', TRUE)
ON CONFLICT (room_number) DO NOTHING;

-- ── RESERVATIONS ─────────────────────────────────────────────────────────────
-- NOTE: room_id values reference the rooms inserted above.
-- We use a sub-select to look up the room by room_number.

INSERT INTO public.reservations (guest_id, guest_name, guest_email, guest_phone, room_id, check_in, check_out, guest_count, special_requests, status, booking_ref)
VALUES
  ('G-2291', 'Margaret Ashworth', 'margaret.a@demo.test', '+1 555 210 4821',
   (SELECT id FROM public.rooms WHERE room_number = '101'),
   '2026-09-01', '2026-09-05', 2,
   'Champagne on arrival, hypoallergenic bedding.',
   'confirmed', 'INP-2026-0001'),

  ('G-3312', 'James Lafferty', 'james.l@demo.test', '+1 555 332 7710',
   (SELECT id FROM public.rooms WHERE room_number = '202'),
   '2026-09-03', '2026-09-07', 1,
   'Late check-in after 11pm.',
   'confirmed', 'INP-2026-0002'),

  ('G-4401', 'Priya Mehta', 'priya.m@demo.test', '+1 555 441 9930',
   (SELECT id FROM public.rooms WHERE room_number = '201'),
   '2026-09-04', '2026-09-09', 2,
   'Vegetarian meal preferences.',
   'confirmed', 'INP-2026-0003'),

  ('G-5520', 'Thomas Bellworth', 'thomas.b@demo.test', '+1 555 552 6614',
   (SELECT id FROM public.rooms WHERE room_number = '103'),
   '2026-09-02', '2026-09-06', 2,
   NULL,
   'confirmed', 'INP-2026-0004'),

  ('G-6631', 'Nadia Okonkwo', 'nadia.o@demo.test', '+1 555 663 8823',
   (SELECT id FROM public.rooms WHERE room_number = '218'),
   '2026-09-01', '2026-09-10', 3,
   'Anniversary stay — flowers in room please.',
   'confirmed', 'INP-2026-0005'),

  ('G-7742', 'Rafael Vega', 'rafael.v@demo.test', '+1 555 774 1192',
   (SELECT id FROM public.rooms WHERE room_number = '210'),
   '2026-09-06', '2026-09-08', 2,
   'Early check-in requested.',
   'confirmed', 'INP-2026-0006'),

  ('G-8853', 'Isabelle Fontaine', 'isabelle.f@demo.test', '+33 6 12 34 56 78',
   (SELECT id FROM public.rooms WHERE room_number = '303'),
   '2026-09-10', '2026-09-15', 4,
   'Corporate stay — invoice required.',
   'confirmed', 'INP-2026-0007'),

  ('G-9964', 'Liam Chandra', 'liam.c@demo.test', '+44 7700 900 123',
   (SELECT id FROM public.rooms WHERE room_number = '115'),
   '2026-09-12', '2026-09-14', 1,
   NULL,
   'confirmed', 'INP-2026-0008')
ON CONFLICT (booking_ref) DO NOTHING;

-- ── DEPOSITS ─────────────────────────────────────────────────────────────────
INSERT INTO public.deposits (reservation_id, amount, status, receipt_url, paid_at)
SELECT
  r.id,
  CASE r.booking_ref
    WHEN 'INP-2026-0001' THEN 150.00
    WHEN 'INP-2026-0002' THEN  95.00
    WHEN 'INP-2026-0003' THEN 110.00
    WHEN 'INP-2026-0004' THEN  60.00
    WHEN 'INP-2026-0005' THEN 200.00
    WHEN 'INP-2026-0006' THEN 130.00
    WHEN 'INP-2026-0007' THEN 300.00
    WHEN 'INP-2026-0008' THEN  65.00
    ELSE 0
  END,
  CASE r.booking_ref
    WHEN 'INP-2026-0001' THEN 'paid'
    WHEN 'INP-2026-0002' THEN 'pending'
    WHEN 'INP-2026-0003' THEN 'paid'
    WHEN 'INP-2026-0004' THEN 'paid'
    WHEN 'INP-2026-0005' THEN 'pending'
    WHEN 'INP-2026-0006' THEN 'paid'
    WHEN 'INP-2026-0007' THEN 'paid'
    WHEN 'INP-2026-0008' THEN 'pending'
    ELSE 'pending'
  END,
  NULL,
  CASE r.booking_ref
    WHEN 'INP-2026-0001' THEN NOW() - INTERVAL '3 days'
    WHEN 'INP-2026-0003' THEN NOW() - INTERVAL '2 days'
    WHEN 'INP-2026-0004' THEN NOW() - INTERVAL '1 day'
    WHEN 'INP-2026-0006' THEN NOW() - INTERVAL '12 hours'
    WHEN 'INP-2026-0007' THEN NOW() - INTERVAL '8 hours'
    ELSE NULL
  END
FROM public.reservations r
ON CONFLICT DO NOTHING;

-- ── REVIEWS ──────────────────────────────────────────────────────────────────
INSERT INTO public.reviews (guest_name, rating, review_text, approved, created_at)
VALUES
  ('Charlotte B.',   5, 'Exceptional service and impeccable attention to detail. The suite exceeded every expectation — truly a world-class experience.',        TRUE, NOW() - INTERVAL '5 days'),
  ('James L.',       5, 'From the moment we arrived, the team made us feel like royalty. The rooftop terrace at sunset is absolutely unforgettable.',             TRUE, NOW() - INTERVAL '8 days'),
  ('Priya M.',       4, 'Beautiful property, immaculate rooms, and the concierge team went above and beyond. Will absolutely return for our anniversary.',         TRUE, NOW() - INTERVAL '14 days'),
  ('Rafael V.',      5, 'The Botanical Suite was breathtaking. The living walls, the spa bath, the butler service — every detail was considered.',                 TRUE, NOW() - INTERVAL '20 days'),
  ('Thomas B.',      4, 'Solid luxury experience. The dining at The Atelier was the highlight. Highly recommend the tasting menu with wine pairing.',              TRUE, NOW() - INTERVAL '30 days'),
  ('Nadia O.',       5, 'We celebrated our anniversary here. The team surprised us with flowers and champagne. Cannot recommend it enough.',                       FALSE, NOW() - INTERVAL '2 days')
ON CONFLICT DO NOTHING;

-- ── AUDIT LOGS (demo seed events) ────────────────────────────────────────────
INSERT INTO public.audit_logs (username, ip_address, event, severity, session_reference, metadata, occurred_at)
VALUES
  ('admin.root',    '192.168.1.104', 'Admin login success',                              'INFO',     'sess_demo_8aB2KxmQ', '{}',                           NOW() - INTERVAL '5 minutes'),
  ('unknown',       '203.112.56.78', 'Failed login attempt: 3 consecutive failures',     'WARN',     'sess_demo_Xp4NrQzL', '{"attempts":3}',               NOW() - INTERVAL '12 minutes'),
  ('jane.staff',    '192.168.1.22',  'Staff login success',                              'INFO',     'sess_demo_Vy7MsPwR', '{}',                           NOW() - INTERVAL '30 minutes'),
  ('admin.root',    '192.168.1.104', 'Account manually locked: tom.front',               'WARN',     'sess_demo_Kd9TqBnE', '{"target":"tom.front"}',       NOW() - INTERVAL '1 hour'),
  ('admin.root',    '192.168.1.104', 'User role changed: jane.staff → admin',            'WARN',     'sess_demo_Jf2LxPuA', '{"from":"staff","to":"admin"}',NOW() - INTERVAL '90 minutes'),
  ('guest@demo',    '0.0.0.0',       'Reservation created: INP-2026-0001 for Margaret Ashworth', 'INFO', 'sess_guest_kp4xmQ', '{"booking_ref":"INP-2026-0001"}', NOW() - INTERVAL '2 hours'),
  ('jane.staff',    '192.168.1.22',  'Deposit marked paid: INP-2026-0003',               'INFO',     'sess_demo_Pr5GhNkX', '{"booking_ref":"INP-2026-0003"}', NOW() - INTERVAL '3 hours'),
  ('admin.root',    '192.168.1.104', 'Schema viewer accessed',                           'INFO',     'sess_demo_8aB2KxmQ', '{}',                           NOW() - INTERVAL '4 hours'),
  ('unknown',       '45.89.234.11',  'Critical: Repeated failed login attempts from external IP', 'CRITICAL', 'sess_demo_Rb8TvHnQ', '{"attempts":15,"source":"external"}', NOW() - INTERVAL '8 hours'),
  ('jane.staff',    '192.168.1.22',  'Reservation deleted: INP-2026-0002',               'WARN',     'sess_demo_Vy7MsPwR', '{"booking_ref":"INP-2026-0002"}', NOW() - INTERVAL '10 hours')
ON CONFLICT DO NOTHING;

-- ── SYSTEM CONFIG ─────────────────────────────────────────────────────────────
INSERT INTO public.system_config (config_key, config_value)
VALUES
  ('hotel_name',          '"InnPulse Boutique Hotel"'),
  ('hotel_email',         '"reservations@innpulse.demo"'),
  ('hotel_phone',         '"+1 (555) 800-INN1"'),
  ('hotel_address',       '"12 Harbourview Crescent, City Centre"'),
  ('default_checkin',     '"15:00"'),
  ('default_checkout',    '"11:00"'),
  ('currency',            '"USD"'),
  ('deposit_pct',         '15'),
  ('allow_reviews',       'true'),
  ('maintenance_mode',    'false'),
  ('online_reservations', 'true'),
  ('deposit_required',    'false')
ON CONFLICT (config_key) DO UPDATE SET config_value = EXCLUDED.config_value, updated_at = NOW();

-- ── Demo Profile Records ──────────────────────────────────────────────────────
-- DEMO CREDENTIALS (create these in Supabase Auth → Users → Add User):
--
--   Admin:  email = admin@innpulse   password = admin@innpulse
--   Staff:  email = staff@innpulse   password = staff@innpulse
--
-- After creating the auth users, the trigger creates profile rows automatically.
-- Then set their roles with:
--
-- UPDATE public.profiles
--   SET role = 'admin', username = 'admin.root'
--   WHERE email = 'admin@innpulse';
--
-- UPDATE public.profiles
--   SET role = 'staff', username = 'jane.staff'
--   WHERE email = 'staff@innpulse';
