-- InnPulse SQLite schema
CREATE TABLE IF NOT EXISTS profiles (
  id          TEXT        PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
  password_hash TEXT NOT NULL,
  username    VARCHAR(80),
  email       VARCHAR(255) UNIQUE NOT NULL COLLATE NOCASE,
  role        VARCHAR(20)  NOT NULL DEFAULT 'staff'
                           CHECK (role IN ('admin', 'staff')),
  active      BOOLEAN      NOT NULL DEFAULT TRUE,
  locked      BOOLEAN      NOT NULL DEFAULT FALSE,
  last_login  TEXT,
  created_at  TEXT  NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- ── ROOMS ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rooms (
  id             TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
  room_number    VARCHAR(10)   UNIQUE NOT NULL,
  room_name      VARCHAR(120)  NOT NULL,
  room_type      VARCHAR(40)   NOT NULL
                               CHECK (room_type IN ('Suite', 'Penthouse', 'Loft', 'Classic', 'Studio')),
  description    TEXT,
  capacity       INTEGER       NOT NULL DEFAULT 2,
  nightly_rate   DECIMAL(10,2) NOT NULL,
  deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status         VARCHAR(20)   NOT NULL DEFAULT 'available'
                               CHECK (status IN ('available', 'reserved', 'occupied', 'maintenance')),
  image_url      TEXT,
  amenities      TEXT         NOT NULL DEFAULT '[]',
  is_featured    BOOLEAN        NOT NULL DEFAULT FALSE,
  created_at     TEXT   NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- ── RESERVATIONS ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reservations (
  id               TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
  guest_id         VARCHAR(20),
  guest_name       VARCHAR(120) NOT NULL,
  guest_email      VARCHAR(255) NOT NULL,
  guest_phone      VARCHAR(30),
  room_id          TEXT         NOT NULL REFERENCES rooms(id) ON DELETE RESTRICT,
  check_in         DATE         NOT NULL,
  check_out        DATE         NOT NULL,
  guest_count      INTEGER      NOT NULL DEFAULT 1,
  special_requests TEXT,
  status           VARCHAR(20)  NOT NULL DEFAULT 'confirmed'
                                CHECK (status IN ('confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show')),
  booking_ref      VARCHAR(30)  UNIQUE NOT NULL,
  created_by       TEXT         REFERENCES profiles(id) ON DELETE SET NULL,
  created_at       TEXT  NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  updated_at       TEXT  NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- ── DEPOSITS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS deposits (
  id             TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
  reservation_id TEXT          NOT NULL REFERENCES reservations(id) ON DELETE CASCADE,
  amount         DECIMAL(10,2) NOT NULL DEFAULT 0,
  status         VARCHAR(20)   NOT NULL DEFAULT 'pending'
                               CHECK (status IN ('pending', 'paid', 'refunded')),
  receipt_url    TEXT,
  paid_at        TEXT,
  created_at     TEXT   NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- ── REVIEWS ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id          TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
  guest_name  VARCHAR(80) NOT NULL,
  rating      INTEGER     NOT NULL CHECK (rating BETWEEN 1 AND 5),
  review_text TEXT        NOT NULL,
  approved    BOOLEAN     NOT NULL DEFAULT FALSE,
  created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- ── AUDIT_LOGS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_logs (
  id                TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
  user_id           TEXT        REFERENCES profiles(id) ON DELETE SET NULL,
  username          VARCHAR(80),
  ip_address        VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
  event             TEXT        NOT NULL,
  severity          VARCHAR(10) NOT NULL DEFAULT 'INFO'
                                CHECK (severity IN ('INFO', 'WARN', 'CRITICAL')),
  session_reference VARCHAR(80),
  metadata          TEXT       NOT NULL DEFAULT '{}',
  occurred_at       TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- ── SYSTEM_CONFIG ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_config (
  id           TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
  config_key   VARCHAR(80) UNIQUE NOT NULL,
  config_value TEXT       NOT NULL DEFAULT 'null',
  updated_at   TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- ── INDEXES ───────────────────────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_reservations_room_id      ON reservations(room_id);
CREATE INDEX IF NOT EXISTS idx_reservations_checkin      ON reservations(check_in);
CREATE INDEX IF NOT EXISTS idx_reservations_status       ON reservations(status);
CREATE INDEX IF NOT EXISTS idx_deposits_reservation_id   ON deposits(reservation_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_occurred_at    ON audit_logs(occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_severity       ON audit_logs(severity);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id        ON audit_logs(user_id);


CREATE TABLE login_attempts (id INTEGER PRIMARY KEY, ip_address TEXT NOT NULL, occurred_at TEXT NOT NULL DEFAULT (datetime('now')));
CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip_address, occurred_at);
