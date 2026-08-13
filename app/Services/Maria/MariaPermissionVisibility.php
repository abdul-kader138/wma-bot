<?php

namespace App\Services\Maria;

class MariaPermissionVisibility
{
    private const RESOURCES = [
        'AcmProductionPlanResource', 'ActionReconciliationResource', 'AgverseOpportunityResource',
        'ApprovalResource', 'AssistantAlertResource', 'AssistantChannelIdentityResource',
        'AssistantProfileResource', 'BookResource', 'ClaimResource', 'CommunicationResource',
        'ConnectorAccountResource', 'ContentItemResource', 'MariaContactResource',
        'MariaProjectResource', 'MariaQualityEventResource', 'MariaTaskResource',
        'MeetingResource', 'PromptVersionResource', 'RelationshipRecommendationResource',
        'WorkflowRunResource',
    ];

    private const PAGES = [
        'ExternalActionControl', 'MariaAcceptanceDashboard', 'MariaDashboard',
    ];

    public static function apply(): void
    {
        $enabled = MariaAccess::enabled();
        $excludedResources = array_values(array_diff(config('filament-shield.exclude.resources', []), self::RESOURCES));
        $excludedPages = array_values(array_diff(config('filament-shield.exclude.pages', []), self::PAGES));

        if (! $enabled) {
            $excludedResources = array_values(array_unique([...$excludedResources, ...self::RESOURCES]));
            $excludedPages = array_values(array_unique([...$excludedPages, ...self::PAGES]));
        }

        config([
            'filament-shield.exclude.resources' => $excludedResources,
            'filament-shield.exclude.pages' => $excludedPages,
            'filament-shield.entities.custom_permissions' => $enabled,
        ]);
    }
}
