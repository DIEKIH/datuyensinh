<?php

namespace App\Observers;

use App\Models\Baiviet;
use App\Jobs\SendArticleToN8n;

class BaivietObserver
{
    /**
     * Handle the Baiviet "created" event.
     *
     * @param  \App\Models\Baiviet  $baiviet
     * @return void
     */
    public function created(Baiviet $baiviet)
    {
        // Dispatch job to send article to n8n (unless auto_publish is set to 2 = Do not post to FB)
        if ((int) $baiviet->auto_publish !== 2) {
            SendArticleToN8n::dispatch($baiviet);
        }
    }

    /**
     * Handle the Baiviet "updated" event.
     *
     * @param  \App\Models\Baiviet  $baiviet
     * @return void
     */
    public function updated(Baiviet $baiviet)
    {
        // Optionally trigger on update if auto_publish changed or if specifically requested
        // For example, if it wasn't published before but now is.
        // SendArticleToN8n::dispatch($baiviet);
    }

    /**
     * Handle the Baiviet "deleted" event.
     *
     * @param  \App\Models\Baiviet  $baiviet
     * @return void
     */
    public function deleted(Baiviet $baiviet)
    {
        //
    }

    /**
     * Handle the Baiviet "restored" event.
     *
     * @param  \App\Models\Baiviet  $baiviet
     * @return void
     */
    public function restored(Baiviet $baiviet)
    {
        //
    }

    /**
     * Handle the Baiviet "force deleted" event.
     *
     * @param  \App\Models\Baiviet  $baiviet
     * @return void
     */
    public function forceDeleted(Baiviet $baiviet)
    {
        //
    }
}
