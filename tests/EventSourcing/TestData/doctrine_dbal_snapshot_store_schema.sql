CREATE TABLE snapshots
(
  id INTEGER PRIMARY KEY NOT NULL,
  aggregate_type TEXT NOT NULL,
  aggregate_id TEXT NOT NULL,
  type TEXT NOT NULL,
  version INTEGER NOT NULL,
  snapshot TEXT NOT NULL
);
