<?php

namespace OptimistDigital\MultiselectField;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;
use Illuminate\Http\Request;
use Auth;
use App\User;

class Multiselect extends Field
{
    public $component = 'multiselect-field';

    protected $pageResponseResolveCallback;
    protected $saveAsJSON = false;

    protected $fromRelationship = false;
    protected $reorderable = false;

    /**
     * Sets the options available for select.
     *
     * @param  array|callable
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function options($options = [])
    {
        if (is_callable($options)) $options = call_user_func($options);
        $options = collect($options ?? []);

        $isOptionGroup = $options->contains(function ($label, $value) {
            return is_array($label);
        });

        if ($isOptionGroup) {
            $_options = $options
                ->map(function ($value, $key) {
                    return collect($value + ['value' => $key]);
                })
                ->groupBy('group')
                ->map(function ($value, $key) {
                    return ['label' => $key, 'values' => $value->map->only(['label', 'value'])->toArray()];
                })
                ->values()
                ->toArray();

            return $this->withMeta(['options' => $_options]);
        }


        return $this->withMeta([
            'options' => $options->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })->values()->all(),
        ]);
    }

    protected function resolveAttribute($resource, $attribute)
    {
        $singleSelect = $this->meta['singleSelect'] ?? false;
        $value = data_get($resource, str_replace('->', '.', $attribute));

        if ($this->saveAsJSON || $singleSelect || $this->fromRelationship) return $value;
        return is_array($value) || is_object($value) ? (array) $value : json_decode($value);
    }

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if ($this->fromRelationship) {
            $value = json_decode($request->input($requestAttribute));
            $attach = $value->attach;
            $detach = $value->detach;
            $reorderable = $this->reorderable;
            $model::saved(function ($model) use ($attribute, $attach, $detach, $reorderable) {
                $model->$attribute()->syncWithoutDetaching($attach);
                $model->$attribute()->detach($detach);
                if ($reorderable) {
                    foreach ($attach as $id) {
                        $model->$attribute()->updateExistingPivot($id, [
                            'sort_order' => array_search($id, $attach),
                        ]);
                    }
                }
            });
            unset($request->$attribute);
            return;
        }

        $singleSelect = $this->meta['singleSelect'] ?? false;
        $value = $request->input($requestAttribute) ?? null;

        if ($singleSelect) {
            $model->{$attribute} = $value;
        } else {
            $model->{$attribute} = $this->saveAsJSON ? $value : json_encode($value);
        }
    }

    /**
     * Allows the field to save an actual JSON array to a SQL JSON column.
     *
     * @param bool $saveAsJSON
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function saveAsJSON($saveAsJSON = true)
    {
        $this->saveAsJSON = $saveAsJSON;
        return $this;
    }

    /**
     * Sets the max number of options the user can select.
     *
     * @param int $max
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function max($max)
    {
        return $this->withMeta(['max' => $max]);
    }

    /**
     * Sets the placeholder value displayed on the field.
     *
     * @param string $placeholder
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function placeholder($placeholder)
    {
        return $this->withMeta(['placeholder' => $placeholder]);
    }

    /**
     * Sets the maximum number of options displayed at once.
     *
     * @param int $optionsLimit
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function optionsLimit($optionsLimit)
    {
        return $this->withMeta(['optionsLimit' => $optionsLimit]);
    }

    /**
     * Enables or disables reordering of the field values.
     *
     * @param bool $reorderable
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function reorderable($reorderable = true)
    {
        $this->reorderable = $reorderable;
        return $this->withMeta(['reorderable' => $reorderable]);
    }

    /**
     * Enables the field to be used as a single select.
     *
     * This forces the value saved to be a single value and not an array.
     *
     * @param bool $singleSelect
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function singleSelect($singleSelect = true)
    {
        return $this->withMeta(['singleSelect' => $singleSelect]);
    }

    /**
     * Enables vue-multiselect's group-select feature which allows the
     * user to select the whole group at once.
     *
     * @param bool $groupSelect
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function groupSelect($groupSelect = true)
    {
        return $this->withMeta(['groupSelect' => $groupSelect]);
    }

    /**
     * Enable other-field dependency.
     *
     * @param string $otherFieldName
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function dependsOn($otherFieldName)
    {
        return $this->withMeta(['dependsOn' => $otherFieldName]);
    }

    /**
     * Set dependency options map. It should be a keyed array of
     * options
     *
     * @param array $options
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function dependsOnOptions(array $options)
    {
        return $this->withMeta(['dependsOnOptions' => $options]);
    }

    public function resolveResponseValue($value, $templateModel)
    {
        $parsedValue = isset($value) ? ($this->saveAsJSON ? $value : json_decode($value)) : null;
        return is_callable($this->pageResponseResolveCallback)
            ? call_user_func($this->pageResponseResolveCallback, $parsedValue, $templateModel)
            : $parsedValue;
    }

    public function resolveForPageResponseUsing(callable $resolveCallback)
    {
        $this->pageResponseResolveCallback = $resolveCallback;
        return $this;
    }

    /**
     * Use relationship to populate options.
     *
     * @param  string  $resource
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function fromRelationship($resource)
    {
        $this->fromRelationship = true;
        $this->resource = $resource;
        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;
        return $this->withMeta(['fromRelationship' => true]);
    }

    /**
     * Group related model in the options list.
     *
     * @param bool $groupRelations
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function groupRelations($groupRelations = true)
    {
        return $this->withMeta(['groupRelations' => $groupRelations]);
    }

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorize(Request $request)
    {
        $authorized = true;
        if ($this->fromRelationship) {
            if ($request->isCreateOrAttachRequest() || $request->isUpdateOrUpdateAttachedRequest()) {
                $relatedResourceName = class_basename($this->resourceClass);
                $parentResourceClass = $request->resource();
                $parentModelClass = $parentResourceClass::$model;
                $authorized = Auth::user()->can("attachAny$relatedResourceName", $parentModelClass);
            }
        }
        return $authorized && parent::authorize($request);
    }

}
