<?php

namespace App\Nova;

use App\Nova\Filters\TelegramIdFilter;
use App\Services\AccountService;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class AccountResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Account>
     */
    public static $model = \App\Models\AccountSecondary::class;

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
        'id', 'telegram.telegram_id'
    ];

    public static function label() {
        return 'Telegram Accounts';
    }

    public static $group = 'Telegram Miniapp';

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
            Text::make('telegram_id', function () {
                return optional($this->telegram)->telegram_id;
            })->sortable()->onlyOnIndex(),
            Text::make('telegram_username', function () {
                return optional($this->telegram)->telegram_username;
            })->sortable()->onlyOnIndex(),


            HasOne::make('Telegram', 'telegram', \App\Nova\TelegramSecondary::class),
            Text::make('total_points'),
            Boolean::make('ambassador')
        ];
    }


    public static function indexQuery(NovaRequest $request, $query)
    {
        $query->select('accounts.*', 'telegrams.telegram_id')
            ->join('telegrams', 'accounts.id', '=', 'telegrams.account_id');


        return $query;
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

    public static function relatableQuery(NovaRequest $request, $query)
    {
        if ($request->search) {
            return $query->whereHas('telegram', function (Builder $q) use ($request) {
                $q->where('telegram_id', 'like', "$request->search");
            });
        }

        return $query;
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [

        ];
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
