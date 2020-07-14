<?php

namespace OptimistDigital\MultiselectField\Http\Controllers;

use Laravel\Nova\Resource;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class AttachController extends Controller
{
    public function create(NovaRequest $request, $parent, $relationship)
    {
        return [
            'available' => $this->getAvailableResources($request, $relationship),
        ];
    }

    public function edit(NovaRequest $request, $parent, $parentId, $relationship)
    {
        return [
            'selected' => $request->findResourceOrFail()->model()->{$relationship}->pluck('id'),
            'available' => $this->getAvailableResources($request, $relationship),
        ];
    }

    public function getAvailableResources($request, $relationship)
    {
        $resourceClass = $request->newResource();

        $field = $resourceClass
            ->availableFields($request)
            ->where('component', 'multiselect-field')
            ->where('attribute', $relationship)
            ->first();

        $query = $field->resourceClass::newModel();

        return $field->resourceClass::relatableQuery($request, $query)->get()
            ->mapInto($field->resourceClass)
            ->filter(function ($resource) use ($request, $field) {
                return true;// $request->newResource()->authorizedToAttach($request, $resource->resource);
            })->map(function($resource) {
                return [
                    'label' => $resource->title(),
                    'value' => $resource->getKey(),
                    'group' => $resource->optionsGroup(),
                ];
            })->sortBy('display')->values();
    }
}