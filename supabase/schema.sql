-- ============================================================
-- InnPulse Boutique Hotel — Database Schema
-- Educational / Demonstration Environment
-- Run this in your Supabase SQL Editor
-- ============================================================

-- ── PROFILES ─────────────────────────────────────────────────────────────────
-- Linked to Supabase auth.users via id
CREATE TABLE IF NOT EXISTS public.profiles (
  id          UUID        PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  username    VARCHAR(80),
  email       VARCHAR(255),
  role        VARCHAR(20)  NOT NULL DEFAULT 'staff'
                           CHECK (role IN ('admin', 'staff')),
  active      BOOLEAN      NOT NULL DEFAULT TRUE,
  locked      BOOLEAN      NOT NULL DEFAULT FALSE,
  last_login  TIMESTAMPTZ,
  created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- ── ROOMS ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.rooms (
  id             UUID          PRIMARY KEY DEFAULT gen_random_uuid(),
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
  amenities      JSONB         NOT NULL DEFAULT '[]',
  is_featured    BOOLEAN        NOT NULL DEFAULT FALSE,
  created_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- ── RESERVATIONS ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.reservations (
  id               UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
  guest_id         VARCHAR(20),
  guest_name       VARCHAR(120) NOT NULL,
  guest_email      VARCHAR(255) NOT NULL,
  guest_phone      VARCHAR(30),
  room_id          UUID         NOT NULL REFERENCES public.rooms(id) ON DELETE RESTRICT,
  check_in         DATE         NOT NULL,
  check_out        DATE         NOT NULL,
  guest_count      INTEGER      NOT NULL DEFAULT 1,
  special_requests TEXT,
  status           VARCHAR(20)  NOT NULL DEFAULT 'confirmed'
                                CHECK (status IN ('confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show')),
  booking_ref      VARCHAR(30)  UNIQUE NOT NULL,
  created_by       UUID         REFERENCES public.profiles(id) ON DELETE SET NULL,
  created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
  updated_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- ── DEPOSITS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.deposits (
  id             UUID          PRIMARY KEY DEFAULT gen_random_uuid(),
  reservation_id UUID          NOT NULL REFERENCES public.reservations(id) ON DELETE CASCADE,
  amount         DECIMAL(10,2) NOT NULL DEFAULT 0,
  status         VARCHAR(20)   NOT NULL DEFAULT 'pending'
                               CHECK (status IN ('pending', 'paid', 'refunded')),
  receipt_url    TEXT,
  paid_at        TIMESTAMPTZ,
  created_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- ── REVIEWS ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.reviews (
  id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  guest_name  VARCHAR(80) NOT NULL,
  rating      INTEGER     NOT NULL CHECK (rating BETWEEN 1 AND 5),
  review_text TEXT        NOT NULL,
  approved    BOOLEAN     NOT NULL DEFAULT FALSE,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── AUDIT_LOGS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.audit_logs (
  id                UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id           UUID        REFERENCES public.profiles(id) ON DELETE SET NULL,
  username          VARCHAR(80),
  ip_address        VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
  event             TEXT        NOT NULL,
  severity          VARCHAR(10) NOT NULL DEFAULT 'INFO'
                                CHECK (severity IN ('INFO', 'WARN', 'CRITICAL')),
  session_reference VARCHAR(80),
  metadata          JSONB       NOT NULL DEFAULT '{}',
  occurred_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── SYSTEM_CONFIG ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public.system_config (
  id           UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  config_key   VARCHAR(80) UNIQUE NOT NULL,
  config_value JSONB       NOT NULL DEFAULT 'null',
  updated_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── INDEXES ───────────────────────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_reservations_room_id      ON public.reservations(room_id);
CREATE INDEX IF NOT EXISTS idx_reservations_checkin      ON public.reservations(check_in);
CREATE INDEX IF NOT EXISTS idx_reservations_status       ON public.reservations(status);
CREATE INDEX IF NOT EXISTS idx_deposits_reservation_id   ON public.deposits(reservation_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_occurred_at    ON public.audit_logs(occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_severity       ON public.audit_logs(severity);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id        ON public.audit_logs(user_id);

-- ── ROW LEVEL SECURITY ───────────────────────────────────────────────────────
ALTER TABLE public.profiles      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rooms         ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reservations  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.deposits      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reviews       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.audit_logs    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.system_config ENABLE ROW LEVEL SECURITY;

-- All tables: open access for demo/educational environment
CREATE POLICY "rooms_all"        ON public.rooms         FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "res_all"          ON public.reservations  FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "dep_all"          ON public.deposits      FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "rev_all"          ON public.reviews       FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "prof_all"         ON public.profiles      FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "audit_all"        ON public.audit_logs    FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "sysconfig_all"    ON public.system_config FOR ALL USING (TRUE) WITH CHECK (TRUE);

-- ── TRIGGER: updated_at on reservations ──────────────────────────────────────
CREATE OR REPLACE FUNCTION public.set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_reservations_updated_at ON public.reservations;
CREATE TRIGGER trg_reservations_updated_at
  BEFORE UPDATE ON public.reservations
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

-- ── TRIGGER: auto-create profile on auth signup ───────────────────────────────
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO public.profiles (id, email, username, role, active, locked)
  VALUES (
    NEW.id,
    NEW.email,
    SPLIT_PART(NEW.email, '@', 1),
    COALESCE(NEW.raw_user_meta_data->>'role', 'staff'),
    TRUE,
    FALSE
  )
  ON CONFLICT (id) DO NOTHING;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();
