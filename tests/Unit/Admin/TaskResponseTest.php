<?php

declare(strict_types=1);
use Svea\Admin\TaskResponse;

test('pending creates task with reference', function () {
    $task = TaskResponse::pending('https://paymentadminapi.svea.com/api/v1/tasks/123');
    expect($task->reference())->toBe('https://paymentadminapi.svea.com/api/v1/tasks/123')
        ->and($task->completed())->toBeFalse()
        ->and($task->failed())->toBeFalse();
});

test('completed returns true when Status is Completed', function () {
    $task = TaskResponse::make(['Status' => 'Completed', 'Resource' => 'https://resource-url']);
    expect($task->completed())->toBeTrue()
        ->and($task->resource())->toBe('https://resource-url');
});

test('failed returns true when Status is Failed', function () {
    $task = TaskResponse::make(['Status' => 'Failed']);
    expect($task->failed())->toBeTrue();
});
