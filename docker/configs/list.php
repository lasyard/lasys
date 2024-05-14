<?php
return [
    Config::TRAITS => [Traits::viewFiles()],
    Config::LIST => [
        'index' => [Server::GET => PageActions::index(), Config::HIDDEN => true],
    ],
];
