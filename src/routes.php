<?php

Route::group([
    'middleware' => ['web'],
    'prefix' => 'selenium-ide-manager',
    'namespace' => 'Plum\SeleniumIdeManager\Http\Controllers'
], function () {
    Route::name('selenium-ide-manager.')->group(function () {
        Route::get('/', 'IndexController@index')->name('index');
        Route::resource('suite', 'IndexController', [
            'parameters' => ['suite' => 'id']
        ])->except([
            'show',
            'update',
            'edit'
        ]);
        Route::get('/suite/change-status/{id}', 'IndexController@changeStatus')->name('change-status');
        Route::get('/sync/google-spreadsheets', 'SyncController@index')->name('sync.index');
        Route::put('/test-case/{id}', 'TestCaseController@update')->name('test-case.update');
        Route::post('/test-case/change-color', 'IndexController@changeColor')->name('test-case.update');
    });
});
