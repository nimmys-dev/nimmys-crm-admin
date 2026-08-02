<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pins how <x-form.select> turns an options array into <option> values.
 *
 * Regression guard: an earlier version used is_int($key) to decide whether a
 * key was a position or a value. That is true for model IDs, so every
 * "ID => name" dropdown silently submitted the label instead of the ID and
 * every exists: rule rejected it.
 */
class FormSelectComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Blade::render() bypasses the web middleware group, which is what
        // normally shares $errors with every view.
        view()->share('errors', new ViewErrorBag);
    }

    private function render(array $options, array $props = []): string
    {
        $attrs = collect($props)->map(fn ($v, $k) => "{$k}=\"{$v}\"")->implode(' ');

        return Blade::render(
            '<x-form.select name="field" :options="$options" '.$attrs.' />',
            ['options' => $options]
        );
    }

    #[Test]
    public function an_id_keyed_map_submits_the_id(): void
    {
        $html = $this->render([7 => 'Jane Doe', 12 => 'John Roe']);

        $this->assertStringContainsString('value="7"', $html);
        $this->assertStringContainsString('value="12"', $html);
        $this->assertStringNotContainsString('value="Jane Doe"', $html);
    }

    #[Test]
    public function a_string_keyed_map_submits_the_key(): void
    {
        $html = $this->render(['active' => 'Active', 'inactive' => 'Inactive']);

        $this->assertStringContainsString('value="active"', $html);
        $this->assertStringContainsString('value="inactive"', $html);
        $this->assertStringNotContainsString('value="Active"', $html);
    }

    #[Test]
    public function a_plain_list_submits_the_label(): void
    {
        // ['Low', 'High'] has keys 0 and 1 — genuine positions, so the label
        // is the value.
        $html = $this->render(['Low', 'High']);

        $this->assertStringContainsString('value="Low"', $html);
        $this->assertStringContainsString('value="High"', $html);
        $this->assertStringNotContainsString('value="0"', $html);
    }

    #[Test]
    public function an_empty_options_array_renders_only_the_placeholder(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('<option value="">', $html);
    }

    #[Test]
    public function the_selected_option_is_marked(): void
    {
        $html = $this->render([7 => 'Jane Doe', 12 => 'John Roe'], ['selected' => 12]);

        $this->assertMatchesRegularExpression('/<option value="12"\s+selected/', $html);
    }
}
