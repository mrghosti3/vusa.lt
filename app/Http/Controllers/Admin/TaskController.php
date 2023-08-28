<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskController extends LaravelResourceController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTaskRequest $request)
    {
        // if separate_tasks is true, create separate tasks for each responsible person
        if ($request->separate_tasks) {
            foreach ($request->responsible_people as $responsible_person) {
                $task = Task::create($request->safe()->only('name', 'taskable_id', 'taskable_type', 'due_date'));
                $task->users()->attach($responsible_person);
            }
        } else {
            $task = Task::create($request->safe()->only('name', 'taskable_id', 'taskable_type', 'due_date'));
            $task->users()->attach($request->responsible_people);
        }

        return back()->with('success', 'Užduotis sėkmingai pridėta');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', [Task::class, $task, $this->authorizer]);

        $validated = $request->validate([
            'name' => 'required',
            'due_date' => 'required',
        ]);

        // change due_date to Carbon object
        $validated['due_date'] = Carbon::createFromTimestamp($validated['due_date'] / 1000);

        $task->update($validated);

        return back()->with('success', 'Užduotis sėkmingai atnaujinta');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', [Task::class, $task, $this->authorizer]);

        $task->delete();

        return back()->with('success', 'Užduotis sėkmingai ištrinta');
    }

    public function updateCompletionStatus(Request $request, Task $task)
    {
        $this->authorize('update', [Task::class, $task, $this->authorizer]);

        if ($request->completed == true) {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        return back()->with('success', 'Užduoties būsena sėkmingai atnaujinta');
    }
}
