/**
 * InnPulse — Supabase Client Initialization
 * Uses only the public anon key (safe for frontend).
 * NEVER place service_role key here.
 */

import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

// ─── Configuration ────────────────────────────────────────────────────────────
// Replace these values with your Supabase project URL and anon key.
// These are the PUBLIC keys — safe for browser use.
const SUPABASE_URL = 'https://your-project-ref.supabase.co';
const SUPABASE_ANON = 'your-anon-key-here';

// ─── Client ───────────────────────────────────────────────────────────────────
export const supabase = createClient(SUPABASE_URL, SUPABASE_ANON, {
    auth: {
        persistSession: true,
        autoRefreshToken: true,
    }
});

export default supabase;
