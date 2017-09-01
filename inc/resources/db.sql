CREATE TABLE `context` (
	`ini_hash`	TEXT NOT NULL,
	`experiment_hash`	TEXT NOT NULL,
	`experiment_ready`	INTEGER DEFAULT 0,
	`running_tasks`	INTEGER DEFAULT 0,
	`status`	TEXT NOT NULL
);

--split

CREATE TABLE `tasks` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`creation_time`	INTEGER NOT NULL,
	`commit_hash`	TEXT NOT NULL,
	`permutation_hash`	TEXT NOT NULL,
	`data`	TEXT NOT NULL
);

--split

INSERT INTO "context" (ini_hash, experiment_hash, status) VALUES ('', '', '');
