CREATE TABLE `context` (
	`ini_hash`	TEXT NOT NULL,
	`last_commit`	TEXT NOT NULL,
	`time_last_pull`	INTEGER NOT NULL DEFAULT 0,
	`running_tasks`	INTEGER NOT NULL DEFAULT 0,
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

INSERT INTO "context" (ini_hash, last_commit, status) VALUES ('', '', '');
