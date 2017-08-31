CREATE TABLE `context` (
	`ini_hash`	TEXT NOT NULL,
	`last_commit`	TEXT NOT NULL,
	`path_log_file`	TEXT NOT NULL,
	`time_last_pull`	INTEGER NOT NULL DEFAULT 0,
	`running_tasks`	INTEGER NOT NULL DEFAULT 0,
	`status`	TEXT NOT NULL
);

--split

CREATE TABLE `tasks` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`creation_time`	INTEGER NOT NULL,
	`commit`	TEXT NOT NULL,
	`permutation`	TEXT NOT NULL,
	`data`	TEXT NOT NULL
);

--split

INSERT INTO "context" (ini_hash, last_commit, path_log_file, status) VALUES ('', '', '', '');