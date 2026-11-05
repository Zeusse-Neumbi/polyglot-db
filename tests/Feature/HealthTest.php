<?php

test('the health endpoint reports the application status', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJson(['status' => 'ok'])
        ->assertJsonStructure(['status', 'application']);
});
