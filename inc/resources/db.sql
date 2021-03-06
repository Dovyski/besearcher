CREATE TABLE `context` (
	`ini_hash`	TEXT NOT NULL,
	`experiment_hash`	TEXT NOT NULL,
	`experiment_ready`	INTEGER DEFAULT 0,
	`status`	TEXT NOT NULL
);

--split

CREATE TABLE `tasks` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`cmd`	TEXT NOT NULL,
	`log_file`	TEXT NOT NULL,
	`working_dir`	TEXT NOT NULL,
	`experiment_hash`	TEXT NOT NULL,
	`permutation_hash`	TEXT NOT NULL,
	`params`	TEXT NOT NULL,
	`creation_time`	INTEGER NOT NULL,
	`priority`	INTEGER NOT NULL DEFAULT 10
);

--split

CREATE TABLE `results` (
	`id`	INTEGER PRIMARY KEY,
	`cmd`	TEXT NOT NULL,
	`cmd_return_code`	INTEGER,
	`log_file`	TEXT NOT NULL,
	`log_file_tags`	TEXT NOT NULL,
	`working_dir`	TEXT NOT NULL,
	`experiment_hash`	TEXT NOT NULL,
	`permutation_hash`	TEXT NOT NULL,
	`params`	TEXT NOT NULL,
	`creation_time`	INTEGER NOT NULL,
	`exec_time_start`	INTEGER NOT NULL,
	`exec_time_end`	INTEGER NOT NULL,
	`progress`	REAL NOT NULL DEFAULT 0,
	`running`	INTEGER NOT NULL
);

--split

CREATE TABLE `analytics` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`metric`	TEXT NOT NULL,
	`min`	REAL,
	`max`	REAL,
	`last_update`	INTEGER DEFAULT 0
);

--split

CREATE TABLE `users` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`name`	TEXT NOT NULL,
	`email`	TEXT NOT NULL,
	`login`	TEXT NOT NULL,
	`password`	TEXT NOT NULL
);

--split

CREATE TABLE `control` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`cmd`	INTEGER NOT NULL,
	`params`	TEXT NOT NULL DEFAULT ''
);

--split

CREATE UNIQUE INDEX idx_login ON users (login);

--split

INSERT INTO `context` (ini_hash, experiment_hash, status) VALUES ('', '', '');
