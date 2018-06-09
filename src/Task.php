<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2018-06-09
 * Time: 01:48
 */

namespace WizOneSolutions\TwoDoToTodoist;


/**
 * Simple value object containing data for Todoist tasks.
 */
final class Task
{
    /**
     * @var string
     *
     * Task content.
     */
    protected $content;

    /**
     * @var string
     *
     * Task project name. If not set, task is put to user’s Inbox. Note: This variable should be processed into a
     * project_id before being sent to the API.
     */
    protected $_projectName;

    /**
     * @var string
     *
     * Represents the _2Do_ project. Can get added to Todoist as a subproject.
     */
    protected $_subProjectName;

    /**
     * @var string
     *
     * Start date of task in 2Do. This does not exist in Todoist, but we may want to do something
     * with it.
     */
    protected $_startDate;

    /**
     * @var \DateInterval
     *
     * 2Do duration of the task, suitable for displaying in various formats.
     */
    protected $_duration;

    /**
     * @var int
     *
     * Non-zero integer value used by clients to sort tasks inside project.
     */
    protected $order;


    /**
     * @var array
     *
     * Names (not IDs) of labels associated with the task.
     */
    protected $_labelNames;

    /**
     * @var int
     *
     * Task priority from 1 (normal) to 4 (urgent).
     */
    protected $priority;

    /**
     * @var string
     *
     * Human defined task due date (ex.: “next Monday”, “Tomorrow”). Value is set using local (not UTC) time.
     */
    protected $due_string;

    /**
     * @var string
     *
     * Specific date in YYYY-MM-DD format relative to user’s timezone.
     */
    protected $due_date;

    /**
     * @var string
     *
     * Specific date and time in RFC3339 format in UTC.
     */
    protected $due_datetime;

    /**
     * @var string
     *
     * 2-letter code specifying language in case due_string is not written in English.
     */
    protected $due_lang;

    public function __construct(array $values)
    {
        // @todo: Validation.
        foreach ($values as $key => $value) {
            if (!in_array($key, $this->keys())) {
                throw new \InvalidArgumentException("No property called {$key} exists. Please check your data structure.");
            }

            $this->{$key} = $value;
        }
    }

    public function toArray() {
        $array = [];
        foreach ($this->keys() as $key) {
            $array->{$key} = $this->{$key};
        }

        return $array;
    }

    protected function keys() {
        return [
            'content',
            '_projectName',
            '_subProjectName',
            '_startDate',
            '_duration',
            'order',
            '_labelNames',
            'priority',
            'due_string',
            'due_date',
            'due_datetime',
            'due_lang',
        ];
    }

}
