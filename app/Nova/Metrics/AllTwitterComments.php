<?php

namespace App\Nova\Metrics;

use App\Models\TwitterAction;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Nova;

class AllTwitterComments extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
//        return $this->countByDays($request, Model::class);
        return $this->result(
            TwitterAction::selectRaw("SUM(CAST(comments AS DECIMAL)) AS comments")->value('comments'));
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
//        return [
//            30 => Nova::__('30 Days'),
//            60 => Nova::__('60 Days'),
//            90 => Nova::__('90 Days'),
//        ];
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
         return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'all-twitter-comments';
    }
}
