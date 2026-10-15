<?php

/*
| Register a mini program here and it appears on the dashboard of every user
| who holds one of its roles. Roles are assignable by the admin automatically.
| 'color' is one of: blue, pink, yellow.
*/
return [
    'admin' => [
        'name' => 'Admin Panel',
        'description' => 'Register users, reset passwords, assign roles.',
        'icon' => '🛠️',
        'symbol' => 'shield-check',
        'color' => 'blue',
        'route' => 'admin.users.index',
        'roles' => ['admin'],
    ],
    'notes' => [
        'name' => 'Notes',
        'description' => 'Jot down your thoughts.',
        'icon' => '📝',
        'symbol' => 'file-text',
        'color' => 'yellow',
        'route' => 'notes.index',
        'roles' => ['notes'],
    ],
    'todo' => [
        'name' => 'To-do',
        'description' => 'Keep track of what to do.',
        'icon' => '✅',
        'symbol' => 'square-check',
        'color' => 'pink',
        'route' => 'todo.index',
        'roles' => ['todo'],
    ],
];
