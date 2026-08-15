<?php

namespace Tests\Unit\Maria;

use App\Services\Maria\Support\JsonSchemaValidator;
use PHPUnit\Framework\TestCase;

class JsonSchemaValidatorTest extends TestCase
{
    private function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'date' => ['type' => 'string'],
            'outcomes' => ['type' => 'array', 'maxItems' => 3, 'items' => ['type' => 'object', 'properties' => [
                'title' => ['type' => 'string'],
            ], 'required' => ['title']]],
            'status' => ['type' => 'string', 'enum' => ['Completed', 'Blocked']],
        ], 'required' => ['date', 'outcomes', 'status']];
    }

    public function test_valid_data_passes(): void
    {
        $errors = JsonSchemaValidator::validate([
            'date' => '2026-08-15', 'outcomes' => [['title' => 'Ship the report']], 'status' => 'Completed',
        ], $this->schema());

        $this->assertSame([], $errors);
    }

    public function test_missing_required_top_level_field_fails(): void
    {
        $errors = JsonSchemaValidator::validate([
            'outcomes' => [], 'status' => 'Completed',
        ], $this->schema());

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("missing required field 'date'", $errors[0]);
    }

    public function test_missing_required_nested_field_fails(): void
    {
        $errors = JsonSchemaValidator::validate([
            'date' => '2026-08-15', 'outcomes' => [['reason' => 'no title here']], 'status' => 'Completed',
        ], $this->schema());

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("outcomes[0]: missing required field 'title'", $errors[0]);
    }

    public function test_too_many_array_items_fails(): void
    {
        $outcomes = array_fill(0, 4, ['title' => 'x']);
        $errors = JsonSchemaValidator::validate([
            'date' => '2026-08-15', 'outcomes' => $outcomes, 'status' => 'Completed',
        ], $this->schema());

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('at most 3 item(s)', $errors[0]);
    }

    public function test_enum_violation_fails(): void
    {
        $errors = JsonSchemaValidator::validate([
            'date' => '2026-08-15', 'outcomes' => [], 'status' => 'InProgress',
        ], $this->schema());

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('status', $errors[0]);
    }

    public function test_wrong_top_level_type_fails(): void
    {
        $errors = JsonSchemaValidator::validate('not-an-object', $this->schema());

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("expected type 'object'", $errors[0]);
    }
}
