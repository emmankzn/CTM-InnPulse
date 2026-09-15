-- ============================================================
-- InnPulse — Fix RLS Policies for Demo Environment
-- Run this in Supabase SQL Editor if you get permission errors
-- ============================================================

-- Drop all existing policies
DO $$ 
DECLARE r RECORD;
BEGIN
  FOR r IN SELECT schemaname, tablename, policyname 
           FROM pg_policies 
           WHERE schemaname = 'public'
  LOOP
    EXECUTE format('DROP POLICY IF EXISTS %I ON %I.%I', r.policyname, r.schemaname, r.tablename);
  END LOOP;
END $$;

-- Re-enable RLS on all tables
ALTER TABLE public.profiles      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rooms         ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reservations  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.deposits      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reviews       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.audit_logs    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.system_config ENABLE ROW LEVEL SECURITY;

-- Create open policies for demo environment
CREATE POLICY "rooms_all"        ON public.rooms         FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "res_all"          ON public.reservations  FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "dep_all"          ON public.deposits      FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "rev_all"          ON public.reviews       FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "prof_all"         ON public.profiles      FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "audit_all"        ON public.audit_logs    FOR ALL USING (TRUE) WITH CHECK (TRUE);
CREATE POLICY "sysconfig_all"    ON public.system_config FOR ALL USING (TRUE) WITH CHECK (TRUE);

SELECT 'RLS policies updated successfully' AS status;
