<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Menjaga bantuan (?) di Reimbursement Settings tetap konsisten dengan layar:
 * setiap ikon punya isi, "Use recommended" menunjuk input yang benar-benar ada,
 * dan tautan panduan menunjuk topik yang ada.
 */
class ReimbursementHelpConfigTest extends TestCase
{
    private const VIEWS = [
        'resources/views/hr-general/reimbursement/settings.blade.php',
        'resources/views/hr-general/reimbursement/components/object-settings.blade.php',
        'resources/views/hr-general/reimbursement/components/step-editor.blade.php',
    ];

    private function items(): array
    {
        return config('reimbursement_help.items');
    }

    private function viewSource(): string
    {
        return implode("\n", array_map(fn ($f) => file_get_contents(base_path($f)), self::VIEWS));
    }

    public function test_every_entry_has_title_and_explanation(): void
    {
        foreach ($this->items() as $key => $item) {
            $this->assertNotSame('', trim($item['title'] ?? ''), "$key has no title");
            $this->assertNotSame('', trim($item['what'] ?? ''), "$key has no explanation");

            foreach ($item['options'] ?? [] as $option) {
                $this->assertCount(2, $option, "$key has a malformed option");
            }
        }
    }

    public function test_every_key_used_in_the_views_exists_and_every_entry_is_used(): void
    {
        $source = $this->viewSource();
        preg_match_all("/'key' => '([a-z_.]+)'/", $source, $m);
        preg_match_all("/'help'\s*=> '([a-z_.]+)'/", $source, $w);
        $used = array_unique(array_merge($m[1], $w[1]));

        foreach ($used as $key) {
            $this->assertArrayHasKey($key, $this->items(), "View uses unknown help key $key");
        }

        foreach (array_keys($this->items()) as $key) {
            $this->assertContains($key, $used, "Help entry $key is not shown anywhere");
        }
    }

    public function test_recommended_values_point_to_real_inputs(): void
    {
        $source = $this->viewSource();

        foreach ($this->items() as $key => $item) {
            foreach (array_keys($item['recommended']['values'] ?? []) as $name) {
                $this->assertTrue(
                    str_contains($source, 'name="' . $name . '"') || str_contains($source, "'" . $name . "' =>") || str_contains($source, '$field'),
                    "$key recommends a value for $name, which is not an input on the page"
                );
            }
        }
    }

    public function test_recommended_values_are_valid_for_their_inputs(): void
    {
        $choices = [
            'access_project' => ['everyone', 'team', 'lead'],
            'access_support' => ['everyone', 'team', 'lead'],
            'policy_project' => ['warn', 'block'],
            'policy_office'  => ['warn', 'block'],
            'policy_support' => ['warn', 'block'],
            'project_order'  => ['admin_first', 'manager_first'],
        ];

        foreach ($this->items() as $item) {
            foreach ($item['recommended']['values'] ?? [] as $name => $value) {
                if (isset($choices[$name])) {
                    $this->assertContains($value, $choices[$name], "$name has an invalid recommended value");
                } else {
                    $this->assertIsBool($value, "$name should be a checkbox value (true or false)");
                }
            }
        }
    }

    public function test_guide_topic_exists_and_text_has_no_long_dashes(): void
    {
        $topics = array_filter([config('reimbursement_help.topic')] + array_column($this->items(), 'topic'));

        foreach ($topics as $topic) {
            $found = false;
            foreach (glob(base_path('resources/guide/en/*.md')) as $file) {
                if (str_contains(file_get_contents($file), "key: $topic\n")) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Guide topic $topic does not exist");
        }

        $this->assertDoesNotMatchRegularExpression('/\x{2014}|\x{2013}/u', json_encode($this->items(), JSON_UNESCAPED_UNICODE));
    }
}
