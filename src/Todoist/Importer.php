<?php

namespace WizOneSolutions\TwoDoToTodoist\Todoist;


use Carbon\CarbonInterval;
use FabianBeiner\Todoist\TodoistClient;
use FabianBeiner\Todoist\TodoistException;
use WizOneSolutions\TwoDoToTodoist\Task;

class Importer
{

    /**
     * @param Task[] $tasks
     * @param array $config
     * @throws \FabianBeiner\Todoist\TodoistException
     */
    public static function import($tasks, $config)
    {
        $api = new TodoistClient($config['api_token']);

        // Go through each task and get it created! Create missing projects along the way.

        // Cache projects and labels upfront to reduce the number of round trips.
        $projects = $api->getAllProjects();
        $projects_by_id = self::indexProjectsById($projects);
        $project_names = self::mapProjectIdsToNames($projects);

        $labels = $api->getAllLabels();
        $labels_by_id = self::indexLabelsById($projects);
        $label_names = self::mapLabelIdsToNames($projects);

        // TODO: Don't forget about simulate
        foreach ($tasks as $task) {
            // Get out an array from the Task object and modify/unset what we need before the API
            // request.
            $api_task = $task->toArray();

            // Resolve project ID.
            $project_name = $api_task['_projectName'];
            if (!empty($project_name)) {
                $project_id = array_search($project_name, $project_names);
                if (empty($config['simulate']) && !$project_id) {
                    if (!$new_project = $api->createProject($project_name)) {
                        throw new TodoistException("Something went wrong creating a new project ({$project_name}). Check what it might be and try again.");
                    }

                    echo "Created project: $project_name" . PHP_EOL;

                    // Update our indexes.
                    $projects[] = $new_project;
                    $project_id = $new_project->id;
                    $projects_by_id[$project_id] = $new_project;
                    $project_names[$project_id] = $new_project->name;
                } elseif ($config['simulate']) {
                    echo "Would create project: {$project_name}" . PHP_EOL;
                    $project_names[] = $project_name;
                }
                $api_task['project_id'] = $project_id;
            } else {
                // Put it in the Inbox.
                unset($api_task['project_id']);
            }

            // Resolve subproject ID.
            $raw_subproject_name = $api_task['_subProjectName'];
            if ($raw_subproject_name) {
                // For projects without a name, this will result in slash-prefixed subproject
                // names, but I think that is OK. It will still visually distinguish them.
                $subproject_name = "$project_name/$raw_subproject_name";
                $subproject_id = array_search($subproject_name, $project_names);
                if (empty($config['simulate']) && !$subproject_id) {
                    if (!$new_subproject = $api->createProject($subproject_name)) {
                        throw new TodoistException("Something went wrong creating a new subproject ({$subproject_name}). Check what it might be and try again.");
                    }

                    echo "Created project: $subproject_name" . PHP_EOL;

                    // Update our indexes.
                    $projects[] = $new_subproject;
                    $subproject_id = $new_subproject->id;
                    $projects_by_id[$subproject_id] = $new_subproject;
                    $project_names[$subproject_id] = $new_subproject->name;
                } elseif ($config['simulate']) {
                    echo "Would create subproject: {$subproject_name}" . PHP_EOL;
                    $project_names[] = $subproject_name;
                }
                $api_task['project_id'] = $subproject_id;
            }
            unset($api_task['_projectName'], $api_task['_subProjectName']);

            if ($api_task['_duration'] instanceof \DateInterval) {
                $api_task['_labelNames'][] = 'duration_' . str_replace(' ', '_',
                        CarbonInterval::instance($api_task['_duration'])->forHumans(true));
            }
            unset($api_task['_duration']);

            if ($api_task['_isNegativePriority']) {
                $api_task['_labelNames'][] = 'negative_priority';
            }
            unset($api_task['_isNegativePriority']);

            // Resolve label ID.
            $task_label_names = $api_task['_labelNames'];

            $label_ids = [];
            foreach ($task_label_names as $label_name) {
                $label_id = array_search($label_name, $label_names);
                if (empty($config['simulate']) && !$label_id) {
                    if (!$new_label = $api->createLabel($label_name)) {
                        throw new TodoistException("Something went wrong creating a new label ({$label_name}). Check what it might be and try again.");
                    }

                    echo "Created label: $label_name" . PHP_EOL;

                    // Update our indexes.
                    $labels[] = $new_label;
                    $label_id = $new_label->id;
                    $labels_by_id[$label_id] = $new_label;
                    $label_names[$label_id] = $new_label->name;
                } elseif ($config['simulate']) {
                    echo "Would create label: {$label_name}" . PHP_EOL;
                    $label_names[] = $label_name;
                }

                $label_ids[] = $label_id;
            }
            unset($api_task['_labelNames']);
            $api_task['label_ids'] = $label_ids;

            $task_comments = [];
            // Prepare task comments to add afterward.
            if ($api_task['_startDate']) {
                $task_comments[] = "Original start date: {$api_task['_startDate']}";
            }
            if ($api_task['_note']) {
                $task_comments[] = $api_task['_note'];
            }
            unset($api_task['_startDate'], $api_task['_note']);

            // Finally, separate out content. It is a separate argument in the library we're using.
            $content = $api_task['content'];
            unset($api_task['content']);

            if (empty($config['simulate'])) {
                // All set. Create the task!
                if (!$new_task = $api->createTask($content, $api_task)) {
                    throw new TodoistException("Something went wrong creating the task {$content}. Check what it might be and try again. Task options: " . print_r($api_task,
                            true));
                }

                echo "Created task: $content" . PHP_EOL;
            } elseif ($config['simulate']) {
                echo "Would create task {$content}: " . print_r($api_task, true) . PHP_EOL;
            }

            // Now add the comments.
            foreach ($task_comments as $task_comment) {
                if (empty($config['simulate']) && isset($new_task)) {
                    $api->createComment('task', $new_task->id, $task_comment);
                    echo "Added comment to above task: {$task_comment}" . PHP_EOL;
                } elseif ($config['simulate']) {
                    echo "Would create comment: {$task_comment}" . PHP_EOL;
                }
            }
        }
    }

    /**
     * @param array $projects
     * @return array
     */
    protected static function indexProjectsById(array $projects)
    {
        $by_id = [];
        foreach ($projects as $project) {
            $by_id[$project->id] = $project;
        }
        return $by_id;
    }

    /**
     * @param array $projects
     * @return array
     */
    protected static function mapProjectIdsToNames(array $projects)
    {
        $mapped = [];
        foreach ($projects as $project) {
            $mapped[$project->id] = $project->name;
        }
        return $mapped;
    }

    /**
     * @param array $labels
     * @return array
     */
    protected static function indexLabelsById(array $labels)
    {
        $by_id = [];
        foreach ($labels as $label) {
            $by_id[$label->id] = $label;
        }
        return $by_id;
    }

    /**
     * @param array $labels
     * @return array
     */
    protected static function mapLabelIdsToNames(array $labels)
    {
        $mapped = [];
        foreach ($labels as $label) {
            $mapped[$label->id] = $label->name;
        }
        return $mapped;
    }

}
