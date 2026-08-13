<?php

namespace App\Services\Maria;

use App\Services\Maria\Tools\AssistantTool;
use App\Services\Maria\Tools\CreateTaskTool;
use App\Services\Maria\Tools\ListCalendarEventsTool;
use App\Services\Maria\Tools\ListPriorityEmailsTool;
use App\Services\Maria\Tools\ListTasksTool;
use App\Services\Maria\Tools\SearchDriveFilesTool;
use InvalidArgumentException;

class ToolRegistry
{
    /** @var array<string, AssistantTool> */
    private array $tools;

    public function __construct(
        ListTasksTool $listTasks,
        CreateTaskTool $createTask,
        ListPriorityEmailsTool $emails,
        ListCalendarEventsTool $calendar,
        SearchDriveFilesTool $drive,
    ) {
        $this->tools = [
            $listTasks->name() => $listTasks, $createTask->name() => $createTask,
            $emails->name() => $emails, $calendar->name() => $calendar, $drive->name() => $drive,
        ];
    }

    public function definitions(): array
    {
        return array_map(fn (AssistantTool $tool) => $tool->definition(), array_values($this->tools));
    }

    public function get(string $name): AssistantTool
    {
        return $this->tools[$name] ?? throw new InvalidArgumentException("Unknown Maria tool: {$name}");
    }
}
