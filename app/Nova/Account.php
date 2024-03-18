<?php

namespace App\Nova;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Account extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Account>
     */
    public static $model = \App\Models\Account::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'wallet', 'twitter_username'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $previousWeek = Carbon::now()->subWeek()->format('W-Y');

        return [
            ID::make()->sortable(),
            Text::make(__('Wallet'), 'wallet')->sortable(),
            Text::make(__('Role'), 'role')->sortable(),
            Text::make(__('Twitter_username'), 'twitter_username')->sortable(),
            Text::make(__('Twitter_id'), 'twitter_id')->onlyOnDetail(),
            Text::make(__('discord_id'), 'discord_id')->onlyOnDetail(),
            Number::make('total_points')->min(0.001)->step(0.001)->sortable(),

            Number::make('claim_points', function () use($previousWeek){
                $claim_points = $this->weeks()
                    ->where('week_number', $previousWeek)
                    ->pluck('claim_points')
                    ->first();
                return $claim_points;
            })->sortable(),

            HasMany::make('Week', 'weeks')->hideFromIndex()

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
