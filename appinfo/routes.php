<?php
return [
    'routes' => [
        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#drivers', 'url' => '/drivers', 'verb' => 'GET'],
        ['name' => 'page#notifications', 'url' => '/notifications', 'verb' => 'GET'],

        // Driver API routes
        ['name' => 'driver#index', 'url' => '/api/drivers', 'verb' => 'GET'],
        ['name' => 'driver#show', 'url' => '/api/drivers/{id}', 'verb' => 'GET'],
        ['name' => 'driver#create', 'url' => '/api/drivers', 'verb' => 'POST'],
        ['name' => 'driver#update', 'url' => '/api/drivers/{id}', 'verb' => 'PUT'],
        ['name' => 'driver#destroy', 'url' => '/api/drivers/{id}', 'verb' => 'DELETE'],
        
        // Important: Search route MUST come after the driver/{id} route to avoid conflicts
        ['name' => 'driver#search', 'url' => '/api/drivers/search', 'verb' => 'GET'],

        // Notification API routes
        ['name' => 'notification#index', 'url' => '/api/notifications', 'verb' => 'GET'],
        ['name' => 'notification#show', 'url' => '/api/notifications/{id}', 'verb' => 'GET'],
        ['name' => 'notification#create', 'url' => '/api/notifications', 'verb' => 'POST'],
        ['name' => 'notification#update', 'url' => '/api/notifications/{id}', 'verb' => 'PUT'],
        ['name' => 'notification#destroy', 'url' => '/api/notifications/{id}', 'verb' => 'DELETE'],
        
        // Import routes
        ['name' => 'import#import', 'url' => '/api/import/drivers', 'verb' => 'POST'],
        
        // Test routes (for development/testing only)
        ['name' => 'test#sendNotification', 'url' => '/api/test/notification/{driverId}/{days}', 'verb' => 'GET'],
    ]
];