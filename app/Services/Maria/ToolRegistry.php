<?php

namespace App\Services\Maria;

use App\Models\User;
use App\Services\Maria\Tools\AssistantTool;
use App\Services\Maria\Tools\CompleteTaskTool;
use App\Services\Maria\Tools\CreateTaskTool;
use App\Services\Maria\Tools\LatestBriefTool;
use App\Services\Maria\Tools\ListApprovalsTool;
use App\Services\Maria\Tools\ListCalendarEventsTool;
use App\Services\Maria\Tools\ListMeetingsTool;
use App\Services\Maria\Tools\ListPriorityEmailsTool;
use App\Services\Maria\Tools\ListProjectsTool;
use App\Services\Maria\Tools\ListRelationshipFollowUpsTool;
use App\Services\Maria\Tools\ListTasksTool;
use App\Services\Maria\Tools\RememberFactTool;
use App\Services\Maria\Tools\SearchDriveFilesTool;
use App\Services\Maria\Tools\UpdateTaskTool;
use InvalidArgumentException;

class ToolRegistry
{
    /** @var array<string, AssistantTool> */
    private array $tools;

    public function __construct(
        ListTasksTool $listTasks,
        CreateTaskTool $createTask,
        CompleteTaskTool $completeTask,
        UpdateTaskTool $updateTask,
        ListProjectsTool $listProjects,
        ListMeetingsTool $listMeetings,
        ListApprovalsTool $listApprovals,
        LatestBriefTool $latestBrief,
        ListRelationshipFollowUpsTool $relationshipFollowUps,
        RememberFactTool $rememberFact,
        ListPriorityEmailsTool $emails,
        ListCalendarEventsTool $calendar,
        SearchDriveFilesTool $drive,
    ) {
        $this->tools = [
            $listTasks->name() => $listTasks, $createTask->name() => $createTask,
            $completeTask->name() => $completeTask, $updateTask->name() => $updateTask,
            $listProjects->name() => $listProjects, $listMeetings->name() => $listMeetings,
            $listApprovals->name() => $listApprovals, $latestBrief->name() => $latestBrief,
            $relationshipFollowUps->name() => $relationshipFollowUps, $rememberFact->name() => $rememberFact,
            $emails->name() => $emails, $calendar->name() => $calendar, $drive->name() => $drive,
        ];
    }

    /** Definitions for only the tools this owner is currently permitted to see (workflow enabled, connector present, etc.). */
    public function definitions(User $owner): array
    {
        return array_values(array_map(
            fn (AssistantTool $tool) => $tool->definition(),
            array_filter($this->tools, fn (AssistantTool $tool) => $tool->availableFor($owner)),
        ));
    }

    /** Resolve a tool by name, re-checking availability so a stale or forged tool_use can't bypass workflow/connector gating. */
    public function get(string $name, User $owner): AssistantTool
    {
        $tool = $this->tools[$name] ?? throw new InvalidArgumentException("Unknown Maria tool: {$name}");

        if (! $tool->availableFor($owner)) {
            throw new InvalidArgumentException("Maria tool not available for this owner: {$name}");
        }

        return $tool;
    }
}
