<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Limpar cache caso crie direto como paid (raro, mas previne bugs)
        if (in_array($order->status, ['paid', 'completed'])) {
            $this->clearWidgetsCache();
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty('status') && in_array($order->status, ['paid', 'completed', 'refunded', 'chargeback'])) {
            $this->clearWidgetsCache();
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        $this->clearWidgetsCache();
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        $this->clearWidgetsCache();
    }

    protected function clearWidgetsCache(): void
    {
        $periods = ['today', '7_days', '30_days', '90_days', 'all_time'];
        foreach ($periods as $period) {
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_chart_{$period}");
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_kpis_{$period}");
        }
        
        \Illuminate\Support\Facades\Cache::forget('admin_top_customers');
    }
}
