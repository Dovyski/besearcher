CREATE TABLE `context` (
	`id`	INTEGER PRIMARY KEY,
	`ini_path`	TEXT NOT NULL,
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
	`data`	TEXT NOT NULL
);

--split

INSERT INTO "context" (id, ini_path, ini_hash, last_commit, path_log_file, status) VALUES (1, '', '', '', '', '');
