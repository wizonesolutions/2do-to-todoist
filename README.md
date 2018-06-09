# 2Do to Todoist

This app adds items to Todoist using its REST API. It accepts a CSV exported from 2Do (`File -> Export to CSV `or `Cmd-Shift-E`) and adds those tasks to Todoist.

## Usage

1. Check the CSV exported from 2Do and ensure it contains what you want. Note that to export all tasks, you must actively "select all" lists from the export dialog. You should only include `Not Done` tasks. (You might want to back up your Done tasks in a separate CSV.)
1. Copy `config-example.yml` to `config.yml` and configure it according to the comments in the file. You can configure list/project mappings.

1. With PHP 7.0+, run `php index.php /path/to/csv.csv` where `/path/to/csv.csv` is the file path to your exported 2Do CSV. I recommend exporting "Not Done" tasks only.

## How it works

1. Tasks will get added to Todoist (or printed out to the terminal if you set `simulate: true` in `config.yml`). Tasks with 2Do Projects will be placed in subprojects under the project corresponding to their list.

1. Durations will be included in the task name (e.g. `[20m]`) and also as Todoist labels (e.g. `@20_minutes`). The former is always in minutes, since it is intended to integrate with Todoist's calendar integration.

## Caveats

If you need something here, open a GitHub issue. If you have time and can implement it yourself, I will review and potentially pull requests. See _Contribute_ below.

1. For best results, set 2Do to English prior to exporting. This is mainly to ensure that dates get exported in the expected format. Other languages MIGHT work since most things don't change, but I've only tested with English. If you export in another language, at least make sure your Todoist date format settings look like they match the format in the exported .csv file.

1. There is no undo, so I recommend using dry-run mode (`simulate: true` in `config.yml`) until you are satisfied with how things look.

1. Project names are used in the mapping, not project IDs. Those would practically require using the API to find out, anyway (it's possible other ways, but cumbersome).

1. I never used negative priority, so I haven't done anything with it. How should I handle it?

1. Make sure your timezone in Todoist is correct. 2Do exports using local time, so the tool imports using it as well.

1. Todoist has no start dates, so the tool adds a comment with the original start date. I figured this was better than adding a label, as I didn't want to flood my account with labels.

1. Note that Todoist has various [limits](https://support.todoist.com/hc/en-us/articles/205064592-What-are-the-task-project-limits-), and the tool does not do anything specific to warn you if you will hit them.

## Contribute

- Main thing is just to follow PSR-1/PSR-2 coding standards as best you can. I'm not going to be too picky.

## License

Note that this is licensed as AGPLv3 (or later). The main implication is that if you use it in a program served over a network (like a web application), the source code of that web application should be available to anyone who can access the web application.*

\* I am not an attorney, and this is not intended as legal advice.

<br>
This application is not created by, affiliated with, or supported by Doist.
