-- Migration 017: remember-me tokens (30-day sliding cookie auth)

CREATE TABLE IF NOT EXISTS remember_tokens (
    id         SERIAL PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT    NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_rt_user_id    ON remember_tokens (user_id);
CREATE INDEX IF NOT EXISTS idx_rt_token_hash ON remember_tokens (token_hash);
