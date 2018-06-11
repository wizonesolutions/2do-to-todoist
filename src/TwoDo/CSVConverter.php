<?php

namespace WizOneSolutions\TwoDoToTodoist\TwoDo;


use WizOneSolutions\TwoDoToTodoist\Task;

class CSVConverter
{

    /**
     * Turn the 2Do CSV into a Todoist-friendly format.
     *
     * @param \League\Csv\Reader $csv
     * @param array $config
     * @return \WizOneSolutions\TwoDoToTodoist\Task[]
     * @throws \League\Csv\Exception
     */
    public static function parse(\League\Csv\Reader $csv, array $config = [])
    {
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $tasks = [];

        foreach ($records as $record) {
            if (!empty($config['ignore_lists']) && in_array($record[' LIST'], $config['ignore_lists'])) {
                continue;
            }

            // There are a few values we can set directly from the CSV.
            $values = [
                'content' => $record['TASK'],
                '_subProjectName' => $record[' PROJECT'],
                '_startDate' => $record[' STARTDATE'],
                '_note' => $record[' NOTE'],
            ];

            $project = $twodo_list = $record[' LIST'];
            $list_mapping = !empty($config['list_mapping']) ? $config['list_mapping'] : [];
            foreach ($list_mapping as $list_to_replace => $todoist_project) {
                if ($twodo_list == $list_to_replace) {
                    $project = $todoist_project;
                }
            }
            $values['_projectName'] = $project;

            if (!empty($config['test_project_name'])) {
                $values['_projectName'] = $config['test_project_name'];
            }

            $due_parts = [];
            foreach ([' DUEDATE', ' DUETIME'] as $due_field) {
                if ($record[$due_field]) {
                    $due_parts[] = $record[$due_field];
                }
            }
            $values['due_string'] = implode(' ', $due_parts);

            if ($record[' DURATION']) {
                // Convert the 2Do duration into a PHP \DateInterval so that the importer
                // can process it as it wishes.
                // 2Do saves the duration as "20 m", "20 h", "20 d", or "20 w" (minutes,
                // hours, days, weeks).
                $normalized_duration = strtoupper(str_replace(' ', '', $record[' DURATION']));
                $interval_spec = 'P';
                switch ($normalized_duration[strlen($normalized_duration) - 1]) {
                    case 'W':
                    case 'D':
                        $interval_spec .= $normalized_duration;
                        break;
                    case 'H':
                    case 'M':
                        $interval_spec .= 'T' . $normalized_duration;
                        break;
                }
                $values['_duration'] = new \DateInterval($interval_spec);
            }

            if ($record[' REPEAT']) {
                // Repeat mappings:
                // - The [FREQUENCY] [WEEKDAY] of each month -> every [FREQUENCY] [WEEKDAY]
                // Other formats don't need conversion; they are already compatible with Todoist's
                // natural language detection.
                $normalized_repeat = strtolower(preg_replace('/The (?P<pattern>.*) of each month/', 'Every \1',
                    $record[' REPEAT']));
                $values['content'] .= " $normalized_repeat";
            }

            // Just add 1 to Priority, and then make sure it's at least 1. If it's not, flag it
            // as negative priority. The importer can add a label later.
            $normalized_priority = $record[' PRIORITY'] + 1;
            if ($normalized_priority === 0) {
                $values['_isNegativePriority'] = true;
                $normalized_priority = 1;
            }
            $values['priority'] = $normalized_priority;

            $values['_labelNames'] = explode(', ', $record[' TAG']);
            if (count($values['_labelNames']) === 1 && empty($values['_labelNames'][0])) {
                $values['_labelNames'] = [];
            }

            // Also use labels for Location and Starred.
            if ($record[' LOCATION']) {
                $location = $record[' LOCATION'];
                $values['_labelNames'][] = self::normalizeLabel($location);
            }
            if ($record[' STAR']) {
                $values['_labelNames'][] = 'starred';
            }

            $tasks[] = new Task($values);
        }

        return $tasks;
    }

    /**
     * @param $label
     * @return string
     */
    protected static function normalizeLabel($label): string
    {
        return str_replace(' ', '_', $label);
    }

}
