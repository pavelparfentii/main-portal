<?php

namespace App\Nova;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Status;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Week extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Week>
     */

//    protected $currentWeekNumber;
//
//    public function __construct($resource, $currentWeekNumber)
//    {
//        parent::__construct($resource);
//        $this->currentWeekNumber = $currentWeekNumber;
//    }

    public static $model = \App\Models\Week::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'week_number';

    public static $perPageViaRelationship = 25;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'week_number',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $currentWeekNumber = Carbon::now()->format('W-Y');
        return [
//            ID::make()->sortable(),
//            $currentDate = Carbon::now();

//            $previousWeek = \App\Models\Week::where('account_id', $account->id)
////                ->where('start_date', '<=', $startOfWeek)
////                ->where('end_date', '>=', $endOfWeek)
//                ->where('active', false)
//                ->select();

            Text::make('Week number', 'week_number'),

            Number::make('points')->min(0.001)->step(0.001)->sortable(),

            Number::make('claim_points')->min(0.001)->step(0.001)->sortable(),
            Number::make('invite_points')->min(0.001)->step(0.001)->sortable(),



            HasMany::make('SafeSoul', 'safeSouls')->hideFromIndex(),

            HasMany::make('Twitter', 'twitters')->hideFromIndex(),

            HasMany::make('DigitalAnimal', 'animals')->hideFromIndex(),

//            Text::make('Claim Status', function () {
//                // Access the current model instance
//                $week = $this->week; // Adjust this based on your model relationships
//
//                // Check the current week number, adjust logic as needed
//                $currentWeekNumber = Carbon::now()->format('W-Y');
//                if ($week->week_number === $currentWeekNumber && $week->isClaimPointsUnused()) {
//                    return 'Not Used';
//                }
//
//                return 'Used';
//            })->exceptOnForms(),

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
