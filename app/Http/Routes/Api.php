<?php
declare(strict_types=1);

/** @var \Laravel\Lumen\Routing\Router $router */

// MailChimp group
$router->group(['prefix' => 'mailchimp', 'namespace' => 'MailChimp'], function () use ($router) {
    // Lists group
    $router->group(['prefix' => 'lists'], function () use ($router) {
        $router->post('/', 'ListsController@create');
        $router->get('/{listId}', 'ListsController@show');
        $router->put('/{listId}', 'ListsController@update');
        $router->delete('/{listId}', 'ListsController@remove');

        // Members group
        $router->group(['prefix' => '{listId}/members'], function () use ($router) {
            $router->get('/', 'ListMembersController@showAll');
            $router->get('/{subscriber_hash}', 'ListMembersController@show');
            $router->post('/', 'ListMembersController@create');
            $router->put('/{subscriber_hash}', 'ListMembersController@update');
            $router->delete('/{subscriber_hash}', 'ListMembersController@remove');

            // Actions group
            $router->group(['prefix' => '{subscriber_hash}/actions'], function () use ($router) {
                $router->post('/delete-permanent', 'ListMembersController@actionDeletePermanent');
            });
        });
    });
});
