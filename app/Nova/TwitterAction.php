<?php

namespace App\Nova;

use App\Nova\Metrics\AllTwitterComments;
use App\Nova\Metrics\AllTwitterLikes;
use App\Nova\Metrics\AllTwitterQuotes;
use App\Nova\Metrics\AllTwitterRetweets;
use App\Nova\Metrics\TwitterActiveAccountsCount;
use App\Nova\Metrics\TwitterTaggedPostsCount;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;

class TwitterAction extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TwitterAction>
     */
    public static $model = \App\Models\TwitterAction::class;

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
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Account')->display(function ($account) {
                if (!empty($account->twitter_username)) {
                    return $account->twitter_username;
                }else{
                    return $account->wallet;
                }

            }),
            Number::make('likes')->sortable(),
            Number::make('retweets')->sortable(),
            Number::make('comments')->sortable(),
            Number::make('quotes')->sortable(),
            Number::make('Tagged post','tagged_post')->sortable(),
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
        return [
            new AllTwitterComments(),
            new AllTwitterLikes(),
            new AllTwitterQuotes(),
            new AllTwitterRetweets(),
            new TwitterTaggedPostsCount(),
            new TwitterActiveAccountsCount()
        ];
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
