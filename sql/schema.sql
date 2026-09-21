-- schema.sql - Postgres (Render) - run via psql $DATABASE_URL -f schema.sql
-- or paste into Render dashboard > Postgres > Query

-- Bots table: one row per BOT_ID / container
CREATE TABLE IF NOT EXISTS bots (
  id                INTEGER PRIMARY KEY,
  proxy_email       VARCHAR(255),
  poll_inbox        VARCHAR(255),
  container_id      VARCHAR(255),
  state             VARCHAR(64),
  sims_count        INTEGER DEFAULT 0,
  current_url       TEXT,
  uptime            VARCHAR(64),
  heartbeat_at      TIMESTAMPTZ,
  created_at        TIMESTAMPTZ DEFAULT NOW(),
  updated_at        TIMESTAMPTZ DEFAULT NOW(),
  auth_token        TEXT,
  token_updated_at  TIMESTAMPTZ
);

-- helper to auto-update updated_at
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END; $$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS bots_updated_at ON bots;
CREATE TRIGGER bots_updated_at BEFORE UPDATE ON bots FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Bot logs: heartbeat history / state changes / free-form logs
CREATE TABLE IF NOT EXISTS bot_logs (
  id          SERIAL PRIMARY KEY,
  bot_id      INTEGER NOT NULL,
  state       VARCHAR(64),
  sims_count  INTEGER,
  current_url TEXT,
  uptime      VARCHAR(64),
  message     TEXT,
  level       VARCHAR(32) DEFAULT 'info',
  created_at  TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_bot_logs_bot_id ON bot_logs(bot_id);
CREATE INDEX IF NOT EXISTS idx_bot_logs_created_at ON bot_logs(created_at);

-- Commands: dashboard -> bot
CREATE TABLE IF NOT EXISTS commands (
  id         SERIAL PRIMARY KEY,
  bot_id     INTEGER NOT NULL,
  cmd        VARCHAR(64) NOT NULL,
  args       TEXT, -- JSON encoded args
  status     VARCHAR(16) DEFAULT 'pending' CHECK (status IN ('pending','acked','done','failed')),
  created_at TIMESTAMPTZ DEFAULT NOW(),
  acked_at   TIMESTAMPTZ,
  updated_at TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_commands_bot_id ON commands(bot_id);
CREATE INDEX IF NOT EXISTS idx_commands_status ON commands(status);

DROP TRIGGER IF EXISTS commands_updated_at ON commands;
CREATE TRIGGER commands_updated_at BEFORE UPDATE ON commands FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Notifications: bot -> dashboard / telegram
CREATE TABLE IF NOT EXISTS notifications (
  id         SERIAL PRIMARY KEY,
  bot_id     INTEGER NOT NULL,
  type       VARCHAR(64) NOT NULL,
  message    TEXT,
  details    TEXT, -- JSON
  priority   VARCHAR(16), -- high|normal|low
  is_read    SMALLINT DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_notifications_bot_id ON notifications(bot_id);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_priority ON notifications(priority);
