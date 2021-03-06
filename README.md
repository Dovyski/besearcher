![Besearcher](www/img/logo/logo-text-padding.png)

Besearcher
======================

Besearcher (*bot researcher*) is a cross-platform tool to help researchers automate and keep track of software-based experiments. The main idea to define a command, its parameters and the possible values of those parameters. Besearcher will then generates all permutations of that command and its parameter values. It will execute each one of them, keeping track of their status (running, finished or aborted) and the output they produced.

## Table of content

- [Features](#features)
- [Installation](#installation)
- [Getting started](#getting-started)
	- [Prerequisites](#prerequisites)
	- [Installing](#installing)
	- [Configuring](#configuring)
- [Usage](#usage)
- [License](#license)
- [Changelog](#changelog)

## Features

Besearcher was created out of a need from a scientific project, so its design values reproducibility and trackability of results. It is easy to use, has minimal dependencies (PHP) and focus on automating repetitive and boring tasks. It also has an (optional) web dashboard that allows users to easily and quickly monitor tasks:

[![Besearcher web dashboard - home](www/img/screenshots/besearcher-home.png?20171115)](./www/img/screenshots/besearcher-home.png)

[![Besearcher web dashboard - tasks](www/img/screenshots/besearcher-tasks.png?20171115)](./www/img/screenshots/besearcher-tasks.png)

[![Besearcher web dashboard - completed sucessful task](www/img/screenshots/besearcher-result-complete.png?20171115)](./www/img/screenshots/besearcher-result-complete.png)

[![Besearcher web dashboard - queue problem](www/img/screenshots/besearcher-queue.png?20171115)](./www/img/screenshots/besearcher-queue.png)

[![Besearcher web dashboard - analytics problem](www/img/screenshots/besearcher-analytics.png?20171115)](./www/img/screenshots/besearcher-analytics.png)

## Getting started

### 0. Prerequisites

You need [PHP](http://php.net) and [Git](https://git-for-windows.github.io/) available in the command line to run Besearcher. If you intend to use the web dashboard (recommended), you need a web server with PHP support. [Wamp](http://www.wampserver.com/en/) is an easy choice.

### 1. Installing

Go to the folder where you want to install Besearcher, e.g. `c:\`:

```
cd c:\
```

Clone Besearcher's repository:

```
git clone https://github.com/Dovyski/besearcher.git besearcher
```

Create a configuration file using the example file provided with Besearcher:

```
copy besearcher\config.ini-example besearcher\config.ini
```

If you intend to use the web dashboard, you also need a configuration file for it:

```
copy besearcher\www\config.ini-example besearcher\www\config.ini
```

### 2. Configuring

Besearcher has two configuration files: `besearcher\config.ini` which controls the behavior of Besearcher, and `besearcher\www\config.ini` which controls the web dashboard.

Let's start with the first one. Open `besearcher\config.ini` in your editor of choice. Search for the line with the directive `data_dir` and inform the **absolute** path of a directory that Besearcher can use to store results and internal data. E.g.:

```
data_dir = "c:\experiment\besearcher\"
```

Then search for the line with the directive `task_cmd`, which is the command Besearcher will use to create permutation and execute them:

```
task_cmd = "test.exe --blah={@data} {@debug} --input={@files}"
```

The section `[task_params]` in the config file contains the values that will be used to parametrize the task indicated by `task_cmd`. Before executing the `task_cmd` string, Besearcher will replace strings like `{@name}` with the values informed in the `[task_params]` section. Any param under that section can be used as `{@name}`. For instance, the following configuration:

```
task_cmd = test.exe --blah={@data} {@debug} --input={@files}

[task_params]
data = hi
debug = -d
files[] = 1
files[] = 2
```

will produce the following commands to be executed:

```
test.exe --blah=hi -d --input=1
test.exe --blah=hi -d --input=2
```

In that case, `{@data}` is replaced by the value of the param named `data` within the `[task_cmd_params]` section, as well as `{@debug}` is replaced by the value of the param `debug` (which is `-d`). The value of `{@files}` will be replaced by `1` and by `2`, because `{@files}` was defined as an array. If more than one param is defined as an array, Besearcher will generate all possible permutations with the informed param values.

### 3. (Optional) Configuring web dashboard

Now let's configure the web dashboard, if you are using it. Open `besearcher\www\config.ini` in your editor of choice. Set the directive `besearcher_ini_file` to the path of the `config.ini` file being used by Besearcher.

For example, if besearcher is installed in `c:\besearcher`, the path to the configuration INI file will be:  

```
besearcher_ini_file = "c:\besearcher\config.ini"
```

In order to make the web dashboard available in the browser, you need to create a virtual host or equivalent in your web server and point its document root to the `www` folder within Besearcher's installation folder.

## Usage

### 1. Running Besearcher
Go to the folder where Besearcher was installed and run:

```
php besearcher.php --ini=config.ini
```

Besearcher will continue to run, outputing log messages to `stdout`. While running, Besearcher will execute all permutations created from the command specified in `task_cmd`.


### 2. Control a running instance of Besearcher

Besearcher might run for a long time if the issued command has several permutations. Because of that, you can control a running instance of Besearcher via command line using the `bc` tool.

Assuming you are in the folder where Besearcher was intalled, just run:

```
cmd\bc --ini=config.ini OPTION
```

where `OPTION` is one of the many available options in `bc`. Below are a few examples of how you can control a running instance of Beseacher.

Show a summary (running tasks, results, etc)
```
cmd\bc --ini=config.ini --status
```

Pause the creation of new tasks:
```
cmd\bc --ini=config.ini --pause
```

Resume creation of new tasks:
```
cmd\bc --ini=config.ini --resume
```

Stop Besearcher:
```
cmd\bc --ini=config.ini --stop
```

Run `cmd\bc --help` for a list of all available options. Additionally check the file [config.ini-example](config.ini-example) for more usage information.

### 3. (Optional) Manage web dashboard users

If you configured the web dashboard of Besearcher, you need to create user(s) to access the dashboard. That is done via command line using the `bcuser` tool.

Assuming you are in the folder where Besearcher was intalled, you can add users by running the command:

```
cmd\bcuser --ini=config.ini --add
```

Run `cmd\bcuser -h` for a complete list of all available options in `bcuser`.

## License

Besearcher is licensed under the terms of the [MIT](https://choosealicense.com/licenses/mit/) Open Source
license and is available for free.

## Changelog

See all changes in the [CHANGELOG](CHANGELOG.md) file.
