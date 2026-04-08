<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminerAccessTest extends TestCase
{
    public function test_adminer_redirects_guests_to_login(): void
    {
        $this->get(route('adminer::index'))
            ->assertRedirect('/');
    }
}
