<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FileIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_the_upload_page(): void
    {
        $this->get(route('home'))->assertRedirect(route('files.upload'));
    }

    public function test_upload_page_uses_the_bootstrap_and_jquery_interface(): void
    {
        $this->get(route('files.upload'))
            ->assertOk()
            ->assertSee('Drop your file here')
            ->assertSee('uploadForm');
    }

    public function test_file_index_route_is_served_by_the_http_presentation_layer(): void
    {
        $response = $this->get(route('files.index'));

        $response
            ->assertOk()
            ->assertSee('Your temporary files')
            ->assertSee('No files yet');
    }
}
