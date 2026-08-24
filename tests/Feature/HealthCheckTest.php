<?php

test('the health check endpoint reports healthy when the database is reachable', function () {
    $this->get('/up')->assertOk();
});
