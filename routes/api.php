<?php

Route::get('/{resource}/attachable/{relationship}', '\OptimistDigital\MultiselectField\Http\Controllers\AttachController@create');
Route::get('/{resource}/{resourceId}/attachable/{relationship}', '\OptimistDigital\MultiselectField\Http\Controllers\AttachController@edit');