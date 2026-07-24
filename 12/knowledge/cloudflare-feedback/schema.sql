CREATE TABLE IF NOT EXISTS guide_feedback (
  page_path TEXT NOT NULL,
  voter_hash TEXT NOT NULL,
  vote INTEGER NOT NULL CHECK (vote IN (-1, 1)),
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  PRIMARY KEY (page_path, voter_hash)
);

CREATE INDEX IF NOT EXISTS idx_guide_feedback_page_vote
ON guide_feedback (page_path, vote);
