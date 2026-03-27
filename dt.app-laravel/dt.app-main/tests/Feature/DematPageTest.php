<?php

it('renders the public demat page for guests', function () {
    $response = $this->get('/demat');

    $response->assertOk();
    $response->assertSee('Tout faire à distance');
    $response->assertSee('Demande de validation');
    $response->assertSee('Demande de remise');
});
