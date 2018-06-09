<?php

namespace WizOneSolutions\TwoDoToTodoist;


class CSVConverter
{

    /**
     * Turn the 2Do CSV into a Todoist-friendly format.
     *
     * @param \League\Csv\Reader $csv
     *
     * @return \WizOneSolutions\TwoDoToTodoist\Task[]
     * @throws \Exception
     * @throws \League\Csv\Exception
     */
    public static function parse(\League\Csv\Reader $csv, array $config = [])
    {
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $tasks = [];

        foreach ($records as $record) {
            $values = [
                'content' => $record['TASK'],
                '_subProjectName' => $record[' PROJECT'],
                '_projectName' => $record[' LIST'],
                '_startDate' => $record[' STARTDATE'],
            ];

            $due_parts = [];
            foreach ([' DUEDATE', ' DUETIME'] as $due_field) {
                $due_parts[] = $due_field;
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

            // Repeat mappings:
            // - The [FREQUENCY] [WEEKDAY] of each month -> [FREQUENCY] [DAY]
            // -

            $tasks[] = new Task($values);
        }
    }

}
