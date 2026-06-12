<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStepCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WorkflowInstance $instance,
        public WorkflowStep $step
    ) {}
}
